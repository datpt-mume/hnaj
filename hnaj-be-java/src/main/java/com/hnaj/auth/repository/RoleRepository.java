package com.hnaj.auth.repository;

import com.hnaj.auth.entity.Role;
import jakarta.persistence.LockModeType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Lock;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.Optional;

public interface RoleRepository extends JpaRepository<Role, Long> {
    Optional<Role> findByName(String name);

    // Lock the seeded admin row before checking/creating the first administrator.
    // The calling service must retain this lock in the same transaction through assignment.
    @Lock(LockModeType.PESSIMISTIC_WRITE)
    @Query("select r from Role r where r.name = :name")
    Optional<Role> findByNameForUpdate(@Param("name") String name);
}
