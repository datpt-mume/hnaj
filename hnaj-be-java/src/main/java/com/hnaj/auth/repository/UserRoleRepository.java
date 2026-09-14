package com.hnaj.auth.repository;

import com.hnaj.auth.entity.Role;
import com.hnaj.auth.entity.UserRole;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;

public interface UserRoleRepository extends JpaRepository<UserRole, Long> {
    // Query the join table on each authorization check; do not cache user-role collections.
    @Query("select ur.role from UserRole ur where ur.user.id = :userId and ur.user.deletedAt is null order by ur.role.id")
    List<Role> findRolesByUserId(@Param("userId") Long userId);

    @Query("select ur.role.name from UserRole ur where ur.user.id = :userId and ur.user.deletedAt is null order by ur.role.id")
    List<String> findRoleNamesByUserId(@Param("userId") Long userId);

    boolean existsByUserIdAndRoleName(Long userId, String roleName);
    boolean existsByUserIdAndRoleId(Long userId, Long roleId);

    @Query("select (count(ur) > 0) from UserRole ur where ur.role.name = :roleName and ur.user.deletedAt is null")
    boolean existsByRoleName(@Param("roleName") String roleName);
}
