package com.hnaj.auth.repository;

import com.hnaj.auth.entity.AccountSetupToken;
import com.hnaj.auth.entity.User;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.time.LocalDateTime;
import java.util.Optional;

public interface AccountSetupTokenRepository extends JpaRepository<AccountSetupToken, Long> {
    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("select t from AccountSetupToken t join fetch t.user u where t.tokenHash = :hash and t.usedAt is null and t.expiresAt > :now and u.deletedAt is null")
    Optional<AccountSetupToken> lockUsableByTokenHash(@Param("hash") String hash, @Param("now") LocalDateTime now);

    @Modifying
    @Query("update AccountSetupToken t set t.usedAt = :now where t.user = :user and t.usedAt is null")
    int invalidateActiveTokens(@Param("user") User user, @Param("now") LocalDateTime now);
}
