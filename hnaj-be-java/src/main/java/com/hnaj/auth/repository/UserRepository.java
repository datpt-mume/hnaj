package com.hnaj.auth.repository;

import com.hnaj.auth.entity.User;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.Optional;

public interface UserRepository extends JpaRepository<User, Long> {
    // Auth lookups exclude soft-deleted accounts as Laravel's SoftDeletes scope does.
    @Query("select u from User u where u.email = :email and u.deletedAt is null")
    Optional<User> findByEmail(@Param("email") String email);

    @Query("select u from User u where u.username = :username and u.deletedAt is null")
    Optional<User> findByUsername(@Param("username") String username);

    @Query("select u from User u where u.googleId = :googleId and u.deletedAt is null")
    Optional<User> findByGoogleId(@Param("googleId") String googleId);

    Optional<User> findByIdAndDeletedAtIsNull(Long id);
    Optional<User> findByEmailAndDeletedAtIsNull(String email);
    Optional<User> findByUsernameAndDeletedAtIsNull(String username);
    Optional<User> findByGoogleIdAndDeletedAtIsNull(String googleId);

    // Unique constraints also cover soft-deleted rows.
    boolean existsByUsername(String username);
    boolean existsByEmail(String email);

    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("select u from User u where u.id = :id and u.deletedAt is null")
    Optional<User> findByIdForUpdate(@Param("id") Long id);
}
