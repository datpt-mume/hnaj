package com.hnaj.auth.security;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.util.HexFormat;
import org.springframework.stereotype.Component;

/** Plaintext tokens never belong in persistence, logs, or redirect URLs. */
@Component
public final class TokenCodec {
    private final SecureRandom random = new SecureRandom();

    public String generate() {
        byte[] bytes = new byte[32];
        random.nextBytes(bytes);
        return HexFormat.of().formatHex(bytes);
    }

    public String hash(String plaintext) {
        try {
            return HexFormat.of().formatHex(MessageDigest.getInstance("SHA-256")
                    .digest(plaintext.getBytes(StandardCharsets.UTF_8)));
        } catch (NoSuchAlgorithmException exception) {
            throw new IllegalStateException("SHA-256 is required by the Java platform.", exception);
        }
    }

    public boolean matches(String plaintext, String expectedHash) {
        return plaintext != null && expectedHash != null
                && MessageDigest.isEqual(hash(plaintext).getBytes(StandardCharsets.US_ASCII),
                        expectedHash.getBytes(StandardCharsets.US_ASCII));
    }
}
