package com.hnaj.auth.repository;

import com.hnaj.auth.entity.AccessToken;
import org.springframework.data.jpa.repository.EntityGraph;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface AccessTokenRepository extends JpaRepository<AccessToken, Long> {
    @EntityGraph(attributePaths = "user")
    Optional<AccessToken> findByTokenHashAndUserStatusAndUserDeletedAtIsNull(
            String tokenHash, String status);
    void deleteByTokenHash(String tokenHash);
    void deleteByUserId(Long userId);
}
