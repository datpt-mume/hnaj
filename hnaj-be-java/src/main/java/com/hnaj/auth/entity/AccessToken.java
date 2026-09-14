package com.hnaj.auth.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "access_tokens")
@Getter
@Setter
@NoArgsConstructor
public class AccessToken {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(columnDefinition = "BIGINT UNSIGNED")
    private Long id;
    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "user_id", nullable = false)
    private User user;
    @Column(name = "token_hash", nullable = false, length = 64, unique = true)
    private String tokenHash;
    @Column(nullable = false)
    private String name;
    @Column(name = "expires_at", columnDefinition = "DATETIME")
    private LocalDateTime expiresAt;
    @Column(name = "last_used_at", columnDefinition = "DATETIME")
    private LocalDateTime lastUsedAt;
    @Column(name = "created_at", columnDefinition = "TIMESTAMP")
    private LocalDateTime createdAt;
    @Column(name = "updated_at", columnDefinition = "TIMESTAMP")
    private LocalDateTime updatedAt;
}
