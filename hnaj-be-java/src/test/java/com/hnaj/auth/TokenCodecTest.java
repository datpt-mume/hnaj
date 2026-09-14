package com.hnaj.auth;

import com.hnaj.auth.security.TokenCodec;
import org.junit.jupiter.api.Test;
import static org.assertj.core.api.Assertions.assertThat;

class TokenCodecTest {
    private final TokenCodec codec = new TokenCodec();

    @Test
    void generatesIndependentOpaqueHexTokens() {
        String first = codec.generate();
        assertThat(first).matches("[a-f0-9]{64}");
        assertThat(codec.generate()).isNotEqualTo(first);
        assertThat(codec.hash(first)).isNotEqualTo(first);
    }

    @Test
    void hashesUsingStandardSha256() {
        assertThat(codec.hash("abc")).isEqualTo(
                "ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad");
        assertThat(codec.matches("abc", codec.hash("abc"))).isTrue();
        assertThat(codec.matches("wrong", codec.hash("abc"))).isFalse();
        assertThat(codec.matches(null, codec.hash("abc"))).isFalse();
        assertThat(codec.matches("abc", null)).isFalse();
    }
}
