package com.hnaj.auth;

import com.hnaj.auth.entity.User;
import com.hnaj.auth.repository.UserRepository;
import com.hnaj.auth.time.TimeConfiguration;
import java.time.Clock;
import java.time.LocalDateTime;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/** PATCH /api/auth/me — only full_name (users.name) is writable; other fields ignored. */
@Service
public class UpdateProfileService {
    private final UserRepository users;
    private final AuthRoleService roles;
    private final Clock clock;

    public UpdateProfileService(UserRepository users, AuthRoleService roles, Clock clock) {
        this.users = users;
        this.roles = roles;
        this.clock = clock;
    }

    @Transactional
    public UserView update(User user, String fullName) {
        LocalDateTime now = TimeConfiguration.now(clock);
        user.setName(fullName);
        user.setUpdatedAt(now);
        users.save(user);
        return UserView.from(user, roles.namesOf(user.getId()));
    }
}
