package com.hnaj.auth;

import com.hnaj.auth.validation.AuthInput;
import com.hnaj.auth.validation.AuthValidationException;
import java.util.Map;
import org.junit.jupiter.api.Test;
import static org.assertj.core.api.Assertions.assertThat;
import static org.assertj.core.api.Assertions.assertThatThrownBy;

class AuthInputTest {
    @Test
    void normalizesUsernameAndEmailButNotPassword() {
        var input = new AuthInput(Map.of("username", " Alice ", "email", " A@Example.com ",
                "password", " secret123 "));
        assertThat(input.username(false)).isEqualTo("alice");
        assertThat(input.email()).isEqualTo("a@example.com");
        assertThat(input.password(false, false)).isEqualTo(" secret123 ");
        input.validate();
    }

    @Test
    void rejectsNonStringWithoutCoercion() {
        var input = new AuthInput(Map.of("username", 123));
        input.username(false);
        assertThatThrownBy(input::validate).isInstanceOfSatisfying(AuthValidationException.class,
                error -> assertThat(error.getErrors()).containsKey("username"));
    }

    @Test
    void setupPasswordDoesNotRequireNumbers() {
        var input = new AuthInput(Map.of("password", "abcdefgh", "password_confirmation", "abcdefgh"));
        input.password(true, false);
        input.validate();
    }

    @Test
    void registrationRequiresNumbersAndMatchingConfirmation() {
        var input = new AuthInput(Map.of("password", "abcdefgh", "password_confirmation", "different"));
        input.password(true, true);
        assertThatThrownBy(input::validate).isInstanceOfSatisfying(AuthValidationException.class,
                error -> assertThat(error.getErrors().get("password")).hasSize(2));
    }

    @Test
    void profileTrimsAndIgnoresReadonlyFields() {
        var input = new AuthInput(Map.of("full_name", " Alice ", "email", "ignored"));
        assertThat(input.required("full_name", 255, true, false)).isEqualTo("Alice");
        input.validate();
    }

    @Test
    void registrationRejectsLeadingUnderscore() {
        var input = new AuthInput(Map.of("username", "_alice"));
        input.username(true);
        assertThatThrownBy(input::validate).isInstanceOf(AuthValidationException.class);
    }

    @Test
    void passwordOverByteCapIsRejectedAsTooLong() {
        // 1025 one-byte characters: passes min(8) but exceeds the bcrypt-safety byte cap.
        var input = new AuthInput(Map.of("password", "a".repeat(1025), "password_confirmation", "a".repeat(1025)));
        input.password(true, false);
        assertThatThrownBy(input::validate).isInstanceOfSatisfying(AuthValidationException.class,
                error -> assertThat(error.getErrors().get("password"))
                        .containsExactly("The password field is too long."));
    }
}
