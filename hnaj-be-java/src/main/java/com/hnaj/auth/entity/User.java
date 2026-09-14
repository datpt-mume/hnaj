package com.hnaj.auth.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "users")
@Getter
@Setter
@NoArgsConstructor
public class User {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(columnDefinition = "BIGINT UNSIGNED")
    private Long id;

    @Column(nullable = false)
    private String name;
    @Column(nullable = false, length = 50, unique = true)
    private String username;
    @Column(nullable = false, unique = true)
    private String email;
    @Column(name = "email_verified_at", columnDefinition = "DATETIME")
    private LocalDateTime emailVerifiedAt;
    @Column(name = "google_id", length = 64, unique = true)
    private String googleId;
    @Column(name = "avatar_url")
    private String avatarUrl;
    @Column(nullable = false)
    private String password;
    @Column(nullable = false)
    private String status = "active";
    @Column(name = "remember_token", length = 100)
    private String rememberToken;
    @Column(name = "created_at", columnDefinition = "TIMESTAMP")
    private LocalDateTime createdAt;
    @Column(name = "updated_at", columnDefinition = "TIMESTAMP")
    private LocalDateTime updatedAt;
    @Column(name = "deleted_at", columnDefinition = "TIMESTAMP")
    private LocalDateTime deletedAt;
}
