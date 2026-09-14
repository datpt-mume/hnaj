package com.hnaj.auth;

import com.hnaj.auth.oauth.UsernameGenerator;
import com.hnaj.auth.repository.UserRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.mockito.Mockito;

import static org.assertj.core.api.Assertions.assertThat;
import static org.mockito.ArgumentMatchers.anyString;
import static org.mockito.Mockito.atLeastOnce;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;

class UsernameGeneratorTest {
    private UserRepository users;
    private UsernameGenerator generator;

    @BeforeEach
    void setUp() {
        users = Mockito.mock(UserRepository.class);
        generator = new UsernameGenerator(users);
    }

    @Test
    void generatesLowerCaseBaseWithSuffixAndChecksDatabase() {
        when(users.existsByUsername(anyString())).thenReturn(false);
        String username = generator.fromEmail("John.Doe@example.com");
        assertThat(username).matches("[a-z0-9._]+_[a-z0-9]{6}");
        assertThat(username).startsWith("john.doe");
        verify(users, atLeastOnce()).existsByUsername(anyString());
    }

    @Test
    void fallsBackToLastCandidateWhenAllTaken() {
        when(users.existsByUsername(anyString())).thenReturn(true);
        String username = generator.fromEmail("alice@example.com");
        assertThat(username).startsWith("alice_").matches("[a-z0-9._]+_[a-z0-9]{6}");
    }

    @Test
    void padsVeryShortLocalPart() {
        when(users.existsByUsername(anyString())).thenReturn(false);
        String username = generator.fromEmail("ab@example.com");
        assertThat(username).startsWith("user");
    }

    @Test
    void stripsAccentsAndInvalidChars() {
        when(users.existsByUsername(anyString())).thenReturn(false);
        String username = generator.fromEmail("tạ Đức@example.com");
        assertThat(username).matches("[a-z0-9_.]+_[a-z0-9]{6}");
    }
}