package com.hnaj.auth.oauth;

import com.hnaj.auth.repository.UserRepository;
import java.text.Normalizer;
import java.util.Locale;
import java.util.concurrent.ThreadLocalRandom;
import org.springframework.stereotype.Component;

/**
 * Generates a unique username from an email local-part, mirroring Laravel UsernameGenerator.
 * The database unique constraint remains the final guard; callers retry on conflict.
 */
@Component
public class UsernameGenerator {
    private static final int MAX_LENGTH = 50;
    private static final int MIN_LENGTH = 3;
    private static final int RANDOM_SUFFIX_LENGTH = 6;

    private final UserRepository users;
    private final java.security.SecureRandom random = new java.security.SecureRandom();

    public UsernameGenerator(UserRepository users) {
        this.users = users;
    }

    public String fromEmail(String email) {
        String base = normalize(email.substring(0, email.indexOf('@')));
        String candidate = withSuffix(base, randomSuffix());

        for (int attempt = 0; attempt < 10; attempt++) {
            if (!users.existsByUsername(candidate)) {
                return candidate;
            }
            candidate = withSuffix(base, randomSuffix());
        }
        return candidate;
    }

    public String generateNew(String email) {
        String base = normalize(email.substring(0, email.indexOf('@')));
        return withSuffix(base, randomSuffix());
    }

    private String normalize(String value) {
        String normalized = stripAccents(value.toLowerCase(Locale.ROOT));
        normalized = normalized.replaceAll("[^a-z0-9_.]", "");
        normalized = normalized.replaceAll("^[._]+|[._]+$", "");
        if (normalized.length() > MAX_LENGTH) {
            normalized = normalized.substring(0, MAX_LENGTH);
        }
        if (normalized.length() < MIN_LENGTH) {
            normalized = "user" + randomSuffix();
        }
        return normalized;
    }

    private String withSuffix(String base, String suffix) {
        int keep = Math.min(base.length(), MAX_LENGTH - suffix.length() - 1);
        return base.substring(0, keep) + "_" + suffix;
    }

    private String randomSuffix() {
        String alphabet = "abcdefghijklmnopqrstuvwxyz0123456789";
        StringBuilder sb = new StringBuilder(RANDOM_SUFFIX_LENGTH);
        for (int i = 0; i < RANDOM_SUFFIX_LENGTH; i++) {
            sb.append(alphabet.charAt(random.nextInt(alphabet.length())));
        }
        return sb.toString();
    }

    private String stripAccents(String value) {
        String decomposed = Normalizer.normalize(value, Normalizer.Form.NFD);
        return decomposed.replaceAll("\\p{M}", "");
    }
}