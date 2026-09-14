package com.hnaj.auth;

import com.hnaj.auth.entity.Role;
import com.hnaj.auth.entity.User;
import com.hnaj.auth.entity.UserRole;
import com.hnaj.auth.repository.RoleRepository;
import com.hnaj.auth.repository.UserRoleRepository;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import java.util.List;
import java.util.NoSuchElementException;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/** Role reads always hit the database so admin revocations apply to the next request. */
@Service
public class AuthRoleService {
    public static final String ROLE_USER = "user";
    public static final String ROLE_SUB_ADMIN = "sub_admin";
    public static final String ROLE_ADMIN = "admin";

    private final RoleRepository roles;
    private final UserRoleRepository userRoles;
    private final Clock clock;

    public AuthRoleService(RoleRepository roles, UserRoleRepository userRoles, Clock clock) {
        this.roles = roles;
        this.userRoles = userRoles;
        this.clock = clock;
    }

    public List<String> namesOf(Long userId) {
        return userRoles.findRoleNamesByUserId(userId);
    }

    public boolean hasRole(Long userId, String roleName) {
        return userRoles.existsByUserIdAndRoleName(userId, roleName);
    }

    public boolean hasAnyRole(Long userId, String... roleNames) {
        for (String name : roleNames) {
            if (hasRole(userId, name)) {
                return true;
            }
        }
        return false;
    }

    @Transactional
    public void assign(User user, String roleName) {
        if (userRoles.existsByUserIdAndRoleName(user.getId(), roleName)) {
            return;
        }
        Role role = roles.findByName(roleName)
                .orElseThrow(() -> new NoSuchElementException("Role not found: " + roleName));
        LocalDateTime now = TimeConfiguration.now(clock);
        UserRole row = new UserRole();
        row.setUser(user);
        row.setRole(role);
        row.setAssignedBy(null);
        row.setAssignedAt(now);
        row.setCreatedAt(now);
        row.setUpdatedAt(now);
        userRoles.save(row);
    }
}
