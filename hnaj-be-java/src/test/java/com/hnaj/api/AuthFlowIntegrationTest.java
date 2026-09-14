package com.hnaj.api;

import com.hnaj.auth.mail.AuthMailer;
import com.hnaj.auth.oauth.GoogleOAuthClient;
import com.hnaj.auth.oauth.GoogleProfile;
import com.hnaj.auth.entity.EmailVerificationToken;
import com.hnaj.auth.repository.AccountSetupTokenRepository;
import com.hnaj.auth.repository.EmailVerificationTokenRepository;
import com.hnaj.auth.repository.RoleRepository;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.repository.UserRoleRepository;
import com.hnaj.auth.security.TokenCodec;
import jakarta.servlet.http.Cookie;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.mockito.ArgumentCaptor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.mock.mockito.MockBean;
import org.springframework.http.MediaType;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.test.context.ActiveProfiles;
import org.junit.jupiter.api.condition.EnabledIfEnvironmentVariable;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;

import java.time.LocalDateTime;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import static org.assertj.core.api.Assertions.assertThat;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.anyInt;
import static org.mockito.ArgumentMatchers.anyString;
import static org.mockito.Mockito.atLeastOnce;
import static org.mockito.Mockito.doThrow;
import static org.mockito.Mockito.never;
import static org.mockito.Mockito.times;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.patch;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * MySQL-backed auth flow tests (profile test → hnaj_java_test). Ports the behavior of
 * the 75+ Laravel auth test methods into focused end-to-end checks. Run explicitly with
 * RUN_MYSQL_IT=true when MySQL (with hnaj_java_test provisioned) is available.
 */
@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
@EnabledIfEnvironmentVariable(named = "RUN_MYSQL_IT", matches = "true")
class AuthFlowIntegrationTest {

    @Autowired private MockMvc mockMvc;
    @Autowired private JdbcTemplate jdbc;
    @Autowired private UserRepository users;
    @Autowired private RoleRepository roles;
    @Autowired private UserRoleRepository userRoles;
    @Autowired private EmailVerificationTokenRepository emailTokens;
    @Autowired private AccountSetupTokenRepository setupTokens;
    @Autowired private PasswordEncoder passwordEncoder;
    @Autowired private TokenCodec codec;
    @Autowired private com.hnaj.auth.ratelimit.RateLimitService rateLimitService;
    @Autowired private com.hnaj.auth.admin.BootstrapAdminService bootstrapAdmin;

    @MockBean private AuthMailer mailer;
    @MockBean private GoogleOAuthClient google;

    @BeforeEach
    void cleanTables() {
        jdbc.update("DELETE FROM access_tokens");
        jdbc.update("DELETE FROM account_setup_tokens");
        jdbc.update("DELETE FROM email_verification_tokens");
        jdbc.update("DELETE FROM user_roles");
        jdbc.update("DELETE FROM users");
        rateLimitService.clearBuckets();
    }

    // ---------------------------------------------------------------- register

    @Test
    void registerCreatesActiveUnverifiedUserWithRoleAndMail() throws Exception {
        mockMvc.perform(post("/api/auth/register")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"alice\",\"full_name\":\"Alice Wonder\","
                                + "\"email\":\"alice@example.com\",\"password\":\"Secret123\","
                                + "\"password_confirmation\":\"Secret123\"}"))
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.success").value(true))
                .andExpect(jsonPath("$.data.user.username").value("alice"))
                .andExpect(jsonPath("$.data.user.full_name").value("Alice Wonder"))
                .andExpect(jsonPath("$.data.user.email_verified").value(false))
                .andExpect(jsonPath("$.data.user.status").value("active"))
                .andExpect(jsonPath("$.data.user.roles[0]").value("user"));

        verify(mailer, times(1)).sendVerificationEmail(any(), anyString(), anyInt());
    }

    @Test
    void registerNormalizesAndValidatesIdentity() throws Exception {
        mockMvc.perform(post("/api/auth/register")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\" ALICE \",\"full_name\":\"A\","
                                + "\"email\":\" Alice@Example.com \",\"password\":\"Secret123\","
                                + "\"password_confirmation\":\"Secret123\"}"))
                .andExpect(status().isCreated())
                .andExpect(jsonPath("$.data.user.username").value("alice"))
                .andExpect(jsonPath("$.data.user.email").value("alice@example.com"));
    }

    @Test
    void registerRejectsDuplicatesWithValidationEnvelope() throws Exception {
        String body = "{\"username\":\"bob\",\"full_name\":\"Bob\",\"email\":\"bob@example.com\","
                + "\"password\":\"Secret123\",\"password_confirmation\":\"Secret123\"}";
        mockMvc.perform(post("/api/auth/register").contentType(MediaType.APPLICATION_JSON).content(body))
                .andExpect(status().isCreated());
        mockMvc.perform(post("/api/auth/register").contentType(MediaType.APPLICATION_JSON).content(body))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.code").value("VALIDATION_ERROR"))
                .andExpect(jsonPath("$.errors.username").isArray());
        mockMvc.perform(post("/api/auth/register")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"bob2\",\"full_name\":\"Bob\",\"email\":\"bob@example.com\","
                                + "\"password\":\"Secret123\",\"password_confirmation\":\"Secret123\"}"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.errors.email").isArray());
    }

    @Test
    void registerMailFailureRollsBackAccount() throws Exception {
        doThrow(new RuntimeException("smtp down"))
                .when(mailer).sendVerificationEmail(any(), anyString(), anyInt());
        mockMvc.perform(post("/api/auth/register")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"carol\",\"full_name\":\"Carol\","
                                + "\"email\":\"carol@example.com\",\"password\":\"Secret123\","
                                + "\"password_confirmation\":\"Secret123\"}"))
                .andExpect(status().isInternalServerError());
        assertThat(users.findByUsername("carol")).isEmpty();
        assertThat(userRoles.findAll()).isEmpty();
    }

    // ---------------------------------------------------------------- login

    @Test
    void loginWithWrongCredentialsReturns401() throws Exception {
        registerUser("dave", "Dave", "dave@example.com", "Secret123");
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"dave\",\"password\":\"WrongPass1\"}"))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.code").value("INVALID_CREDENTIALS"));
    }

    @Test
    void loginUnverifiedEmailReturns403() throws Exception {
        registerUser("eve", "Eve", "eve@example.com", "Secret123");
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"eve\",\"password\":\"Secret123\"}"))
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.code").value("EMAIL_NOT_VERIFIED"));
    }

    @Test
    void loginSuspendedAccountReturns403() throws Exception {
        long id = registerUser("frank", "Frank", "frank@example.com", "Secret123");
        jdbc.update("UPDATE users SET status='suspended' WHERE id=?", id);
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"frank\",\"password\":\"Secret123\"}"))
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.code").value("ACCOUNT_NOT_ACTIVE"));
    }

    @Test
    void loginAdminAccountViaUserEndpointReturns403() throws Exception {
        createAdmin("grace", "Grace", "grace@example.com", "Secret123");
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"grace\",\"password\":\"Secret123\"}"))
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.code").value("FORBIDDEN_ROLE"));
    }

    // -------------------------------------------------------- verify + resend

    @Test
    void verifyTokenThenLogin() throws Exception {
        long id = registerUser("heidi", "Heidi", "heidi@example.com", "Secret123");
        String token = issueVerificationToken(id);

        mockMvc.perform(post("/api/auth/email/verify")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"token\":\"" + token + "\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.email_verified").value(true))
                .andExpect(jsonPath("$.message").value("Email verified successfully. You can sign in now."));

        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"heidi\",\"password\":\"Secret123\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.token").isNotEmpty())
                .andExpect(jsonPath("$.data.user.email_verified").value(true));
    }

    @Test
    void verifyAlreadyVerifiedReturns409WithoutConsuming() throws Exception {
        long id = registerUser("ivan", "Ivan", "ivan@example.com", "Secret123");
        String token = issueVerificationToken(id);
        jdbc.update("UPDATE users SET email_verified_at=NOW() WHERE username='ivan'");

        mockMvc.perform(post("/api/auth/email/verify")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"token\":\"" + token + "\"}"))
                .andExpect(status().isConflict())
                .andExpect(jsonPath("$.code").value("EMAIL_ALREADY_VERIFIED"));

        // Token must remain unused → can be consumed after account marked verified again:
        // Laravel consumes only via a user that is not already verified; here we assert
        // used_at stayed NULL (the token row still exists and is usable).
        assertThat(emailTokens.findUsableByTokenHash(codec.hash(token), LocalDateTime.now()))
                .isPresent();
    }

    @Test
    void verifyInvalidOrExpiredTokenReturns422() throws Exception {
        mockMvc.perform(post("/api/auth/email/verify")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"token\":\"0000000000000000000000000000000000000000000000000000000000000000\"}"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.code").value("INVALID_VERIFICATION_TOKEN"));
    }

    @Test
    void resendIsNeutralForUnknownAndVerifiedAccounts() throws Exception {
        mockMvc.perform(post("/api/auth/email/resend")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"email\":\"nobody@example.com\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data").doesNotExist())
                .andExpect(jsonPath("$.message").value("If the email address needs verification, a new link has been sent."));

        registerUser("judy", "Judy", "judy@example.com", "Secret123");
        jdbc.update("UPDATE users SET email_verified_at=NOW() WHERE username='judy'");
        mockMvc.perform(post("/api/auth/email/resend")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"email\":\"judy@example.com\"}"))
                .andExpect(status().isOk());
        verify(mailer, never()).sendVerificationEmail(any(), anyString(), anyInt());
    }

    @Test
    void resendIssuesNewTokenAndInvalidatesPrevious() throws Exception {
        long id = registerUser("kent", "Kent", "kent@example.com", "Secret123");
        String first = issueVerificationToken(id);

        mockMvc.perform(post("/api/auth/email/resend")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"email\":\"kent@example.com\"}"))
                .andExpect(status().isOk());
        verify(mailer, times(1)).sendVerificationEmail(any(), anyString(), anyInt());
        String second = captureLastVerificationToken();

        assertThat(first).isNotEqualTo(second);
        // Old token is invalidated (used_at set).
        assertThat(emailTokens.findUsableByTokenHash(codec.hash(first), LocalDateTime.now()))
                .isEmpty();

        mockMvc.perform(post("/api/auth/email/verify")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"token\":\"" + second + "\"}"))
                .andExpect(status().isOk());
    }

    // --------------------------------------------------------- account setup

    @Test
    void accountSetupSetsPasswordVerifiesEmailButKeepsStatus() throws Exception {
        long id = registerUser("leo", "Leo", "leo@example.com", "Secret123");
        String plain = codec.generate();
        jdbc.update("INSERT INTO account_setup_tokens (user_id, token_hash, expires_at, created_at) "
                        + "VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())",
                id, codec.hash(plain));

        mockMvc.perform(post("/api/auth/account/setup")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"token\":\"" + plain + "\",\"password\":\"NewPass123\","
                                + "\"password_confirmation\":\"NewPass123\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.email_verified").value(true));

        String status = jdbc.queryForObject("SELECT status FROM users WHERE id=?", String.class, id);
        assertThat(status).isEqualTo("active");

        // New password works; old one no longer does.
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"leo\",\"password\":\"NewPass123\"}"))
                .andExpect(status().isOk());
        mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"leo\",\"password\":\"Secret123\"}"))
                .andExpect(status().isUnauthorized());
    }

    @Test
    void accountSetupConsumesTokenOnce() throws Exception {
        long id = registerUser("mary", "Mary", "mary@example.com", "Secret123");
        String plain = codec.generate();
        jdbc.update("INSERT INTO account_setup_tokens (user_id, token_hash, expires_at, created_at) "
                        + "VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())",
                id, codec.hash(plain));
        String body = "{\"token\":\"" + plain + "\",\"password\":\"NewPass123\","
                + "\"password_confirmation\":\"NewPass123\"}";
        mockMvc.perform(post("/api/auth/account/setup")
                        .contentType(MediaType.APPLICATION_JSON).content(body)).andExpect(status().isOk());
        mockMvc.perform(post("/api/auth/account/setup")
                        .contentType(MediaType.APPLICATION_JSON).content(body))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.code").value("INVALID_VERIFICATION_TOKEN"));
    }

    // ------------------------------------------------------------- me/profile

    @Test
    void meAndUpdateProfileWithBearer() throws Exception {
        registerUser("nick", "Nick", "nick@example.com", "Secret123");
        markVerified("nick");
        String token = login("nick", "Secret123");

        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.username").value("nick"))
                .andExpect(jsonPath("$.data.user.roles[0]").value("user"));

        mockMvc.perform(patch("/api/auth/me")
                        .header("Authorization", "Bearer " + token)
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"full_name\":\"Nick New\",\"username\":\"hacked\",\"email\":\"x@y.com\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.full_name").value("Nick New"))
                .andExpect(jsonPath("$.data.user.username").value("nick"))
                .andExpect(jsonPath("$.data.user.email").value("nick@example.com"));

        // A fresh token carries the configured non-null TTL (opaque token default 24h).
        Long expiryCount = jdbc.queryForObject(
                "SELECT COUNT(*) FROM access_tokens WHERE expires_at IS NOT NULL", Long.class);
        assertThat(expiryCount).isNotNull();
        assertThat(expiryCount).isGreaterThanOrEqualTo(1);
    }

    @Test
    void logoutRevokesOnlyTheCurrentToken() throws Exception {
        registerUser("otto", "Otto", "otto@example.com", "Secret123");
        markVerified("otto");
        String token1 = login("otto", "Secret123");
        String token2 = login("otto", "Secret123");

        mockMvc.perform(post("/api/auth/logout").header("Authorization", "Bearer " + token1))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.message").value("Signed out successfully."));

        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token1))
                .andExpect(status().isUnauthorized());
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token2))
                .andExpect(status().isOk());
    }

    // ----------------------------------------------------------------- admin

    @Test
    void adminLoginMeAndLogout() throws Exception {
        createAdmin("pat", "Pat", "pat@example.com", "Secret123");

        MvcResult result = mockMvc.perform(post("/api/admin/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"pat\",\"password\":\"Secret123\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.roles[0]").value("admin"))
                .andExpect(jsonPath("$.message").value("Signed in to the admin area successfully."))
                .andReturn();
        String token = jsonToken(result);

        mockMvc.perform(get("/api/admin/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.roles[0]").value("admin"));
        mockMvc.perform(post("/api/admin/auth/logout").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk());
        mockMvc.perform(get("/api/admin/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isUnauthorized());
    }

    @Test
    void roleRevocationAppliesToNextRequest() throws Exception {
        registerUser("quin", "Quin", "quin@example.com", "Secret123");
        markVerified("quin");
        String token = login("quin", "Secret123");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk());

        // Remove the user role → next request must be 403 (roles read from DB each time).
        jdbc.update("DELETE FROM user_roles WHERE user_id=(SELECT id FROM users WHERE username='quin')");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isForbidden())
                .andExpect(jsonPath("$.code").value("FORBIDDEN_ROLE"));
    }

    @Test
    void suspendedAccountBearerTokenIsRejected() throws Exception {
        registerUser("saul", "Saul", "saul@example.com", "Secret123");
        markVerified("saul");
        String token = login("saul", "Secret123");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk());

        // Suspend the account → existing token must stop authenticating immediately.
        jdbc.update("UPDATE users SET status='suspended' WHERE username='saul'");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.code").value("UNAUTHENTICATED"));
    }

    @Test
    void softDeletedAccountBearerTokenIsRejected() throws Exception {
        registerUser("dean", "Dean", "dean@example.com", "Secret123");
        markVerified("dean");
        String token = login("dean", "Secret123");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isOk());

        // Soft-delete the account → existing token must stop authenticating immediately.
        jdbc.update("UPDATE users SET deleted_at=NOW() WHERE username='dean'");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.code").value("UNAUTHENTICATED"));
    }

    @Test
    void expiredBearerTokenIsRejected() throws Exception {
        registerUser("ivan", "Ivan", "ivan@example.com", "Secret123");
        markVerified("ivan");
        String token = login("ivan", "Secret123");

        // Age the token past the configured TTL (default 24h) → resolve must drop it.
        jdbc.update("UPDATE access_tokens SET expires_at=DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        mockMvc.perform(get("/api/auth/me").header("Authorization", "Bearer " + token))
                .andExpect(status().isUnauthorized())
                .andExpect(jsonPath("$.code").value("UNAUTHENTICATED"));
    }

    // ---------------------------------------------------------------- google

    @Test
    void googleRedirectCallbackExchangeHappyPath() throws Exception {
        when(google.isConfigured()).thenReturn(true);
        when(google.fetchProfile(anyString())).thenReturn(new GoogleProfile(
                "g-1", "gina@example.com", "Gina", "https://avatar", true));
        when(google.buildAuthorizationUrl(anyString()))
                .thenAnswer(inv -> "https://accounts.google.com/o/oauth2/v2/auth?state=" + inv.getArgument(0));

        MvcResult redirect = mockMvc.perform(get("/api/auth/google/redirect"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.authorization_url").isString())
                .andReturn();
        String cookie = parseCookie(redirect.getResponse().getHeader("Set-Cookie"));
        String state = extractQueryParam(redirect.getResponse().getContentAsString(), "authorization_url", "state");

        MvcResult callback = mockMvc.perform(get("/api/auth/google/callback")
                        .cookie(new Cookie("hnaj_google_oauth_flow", cookie))
                        .param("code", "google-code").param("state", state))
                .andExpect(status().isFound())
                .andReturn();
        String location = callback.getResponse().getHeader("Location");
        assertThat(location).contains("/auth/google/callback?code=");
        String exchangeCode = extractQueryParam(location, null, "code");

        mockMvc.perform(post("/api/auth/google/exchange")
                        .cookie(new Cookie("hnaj_google_oauth_flow", cookie))
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"code\":\"" + exchangeCode + "\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.token").isNotEmpty())
                .andExpect(jsonPath("$.data.user.email").value("gina@example.com"))
                .andExpect(jsonPath("$.data.user.email_verified").value(true))
                .andExpect(jsonPath("$.data.user.roles[0]").value("user"));
    }

    @Test
    void googleExchangeCodeIsSingleUseAndCookieProtected() throws Exception {
        when(google.isConfigured()).thenReturn(true);
        when(google.fetchProfile(anyString())).thenReturn(new GoogleProfile(
                "g-2", "hank@example.com", "Hank", null, true));
        when(google.buildAuthorizationUrl(anyString()))
                .thenAnswer(inv -> "https://accounts.google.com/o/oauth2/v2/auth?state=" + inv.getArgument(0));

        MvcResult redirect = mockMvc.perform(get("/api/auth/google/redirect")).andExpect(status().isOk()).andReturn();
        String cookie = parseCookie(redirect.getResponse().getHeader("Set-Cookie"));
        String state = extractQueryParam(redirect.getResponse().getContentAsString(), "authorization_url", "state");

        MvcResult callback = mockMvc.perform(get("/api/auth/google/callback")
                        .cookie(new Cookie("hnaj_google_oauth_flow", cookie))
                        .param("code", "c1").param("state", state))
                .andExpect(status().isFound()).andReturn();
        String exchangeCode = extractQueryParam(callback.getResponse().getHeader("Location"), null, "code");

        // Laravel pulls (consumes) the code BEFORE checking the cookie hash, so a wrong
        // cookie burns the one-time code: both attempts must fail with GOOGLE_AUTH_FAILED.
        mockMvc.perform(post("/api/auth/google/exchange")
                        .cookie(new Cookie("hnaj_google_oauth_flow", "wrong-cookie"))
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"code\":\"" + exchangeCode + "\"}"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.code").value("GOOGLE_AUTH_FAILED"));

        // Replay with the correct cookie must still fail (code was consumed once).
        mockMvc.perform(post("/api/auth/google/exchange")
                        .cookie(new Cookie("hnaj_google_oauth_flow", cookie))
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"code\":\"" + exchangeCode + "\"}"))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.code").value("GOOGLE_AUTH_FAILED"));
    }

    @Test
    void googleCallbackWithoutCookieFailsAndRedirects() throws Exception {
        when(google.isConfigured()).thenReturn(true);
        when(google.buildAuthorizationUrl(anyString()))
                .thenAnswer(inv -> "https://accounts.google.com/o/oauth2/v2/auth?state=" + inv.getArgument(0));
        MvcResult redirect = mockMvc.perform(get("/api/auth/google/redirect")).andExpect(status().isOk()).andReturn();
        String state = extractQueryParam(redirect.getResponse().getContentAsString(), "authorization_url", "state");

        mockMvc.perform(get("/api/auth/google/callback")
                        .param("code", "c2").param("state", state))
                .andExpect(status().isFound())
                .andExpect(result -> assertThat(result.getResponse().getHeader("Location"))
                        .contains("error=GOOGLE_AUTH_FAILED"));
    }

    // ------------------------------------------------------------- bootstrap

    @Test
    void bootstrapAdminCreatesFirstAdminAndAllowsSignIn() throws Exception {
        assertThat(bootstrapAdmin.adminIsCreated()).isFalse();
        bootstrapAdmin.create("root", "Root Admin", "root@example.com", "Secret123");
        assertThat(bootstrapAdmin.adminIsCreated()).isTrue();

        mockMvc.perform(post("/api/admin/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"root\",\"password\":\"Secret123\"}"))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.data.user.roles[0]").value("admin"));
    }

    @Test
    void bootstrapAdminRejectsSecondAdminAndTakenIdentity() throws Exception {
        bootstrapAdmin.create("root", "Root Admin", "root@example.com", "Secret123");

        org.assertj.core.api.Assertions.assertThatThrownBy(() ->
                bootstrapAdmin.create("root2", "Root Two", "root2@example.com", "Secret123"))
                .isInstanceOf(IllegalStateException.class);

        // Username already used by a regular user → rejected.
        registerUser("taken", "Taken", "taken@example.com", "Secret123");
        org.assertj.core.api.Assertions.assertThatThrownBy(() ->
                bootstrapAdmin.create("taken", "Another", "another@example.com", "Secret123"))
                .isInstanceOf(IllegalStateException.class);

        // Email already used → rejected.
        org.assertj.core.api.Assertions.assertThatThrownBy(() ->
                bootstrapAdmin.create("another", "Another", "taken@example.com", "Secret123"))
                .isInstanceOf(IllegalStateException.class);
    }

    // ---------------------------------------------------------------- helpers

    private long registerUser(String username, String fullName, String email, String password) {
        long roleId = jdbc.queryForObject("SELECT id FROM roles WHERE name='user'", Long.class);
        jdbc.update("INSERT INTO users (name, username, email, password, status, created_at, updated_at) "
                        + "VALUES (?, ?, ?, ?, 'active', NOW(), NOW())",
                fullName, username, email, passwordEncoder.encode(password));
        long userId = jdbc.queryForObject("SELECT id FROM users WHERE username=?", Long.class, username);
        jdbc.update("INSERT INTO user_roles (user_id, role_id, assigned_at, created_at, updated_at) "
                + "VALUES (?, ?, NOW(), NOW(), NOW())", userId, roleId);
        return userId;
    }

    private void createAdmin(String username, String fullName, String email, String password) {
        long roleId = jdbc.queryForObject("SELECT id FROM roles WHERE name='admin'", Long.class);
        jdbc.update("INSERT INTO users (name, username, email, password, status, email_verified_at, created_at, updated_at) "
                        + "VALUES (?, ?, ?, ?, 'active', NOW(), NOW(), NOW())",
                fullName, username, email, passwordEncoder.encode(password));
        long userId = jdbc.queryForObject("SELECT id FROM users WHERE username=?", Long.class, username);
        jdbc.update("INSERT INTO user_roles (user_id, role_id, assigned_at, created_at, updated_at) "
                + "VALUES (?, ?, NOW(), NOW(), NOW())", userId, roleId);
    }

    private void markVerified(String username) {
        jdbc.update("UPDATE users SET email_verified_at=NOW() WHERE username=?", username);
    }

    /** Inserts a usable verification token for the given user and returns its plaintext. */
    private String issueVerificationToken(long userId) {
        String plain = codec.generate();
        jdbc.update("INSERT INTO email_verification_tokens (user_id, token_hash, expires_at, created_at) "
                        + "VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())",
                userId, codec.hash(plain));
        return plain;
    }

    private String login(String username, String password) throws Exception {
        MvcResult result = mockMvc.perform(post("/api/auth/login")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content("{\"username\":\"" + username + "\",\"password\":\"" + password + "\"}"))
                .andExpect(status().isOk())
                .andReturn();
        return jsonToken(result);
    }

    private String jsonToken(MvcResult result) throws Exception {
        Matcher matcher = Pattern.compile("\"token\"\\s*:\\s*\"([^\"]+)\"")
                .matcher(result.getResponse().getContentAsString());
        assertThat(matcher.find()).as("token in response").isTrue();
        return matcher.group(1);
    }

    private String captureLastVerificationToken() {
        ArgumentCaptor<String> urlCaptor = ArgumentCaptor.forClass(String.class);
        verify(mailer, atLeastOnce()).sendVerificationEmail(any(), urlCaptor.capture(), anyInt());
        java.util.List<String> urls = urlCaptor.getAllValues();
        String url = urls.get(urls.size() - 1);
        Matcher matcher = Pattern.compile("token=([^&]+)").matcher(url);
        assertThat(matcher.find()).as("token in mail url").isTrue();
        return matcher.group(1);
    }

    private String parseCookie(String setCookie) {
        assertThat(setCookie).isNotNull();
        return setCookie.split(";")[0].split("=", 2)[1];
    }

    private String extractQueryParam(String uri, String jsonField, String param) {
        String target = uri;
        if (jsonField != null) {
            Matcher m = Pattern.compile("\"" + jsonField + "\"\\s*:\\s*\"([^\"]+)\"").matcher(uri);
            assertThat(m.find()).as(jsonField + " in body").isTrue();
            target = m.group(1);
        }
        Matcher m = Pattern.compile("[?&]" + param + "=([^&]+)").matcher(target);
        assertThat(m.find()).as(param + " in " + target).isTrue();
        return m.group(1);
    }
}