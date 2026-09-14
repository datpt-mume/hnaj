package com.hnaj.auth.validation;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

/** Validates JSON types before coercion and preserves Laravel field error arrays. */
public final class AuthInput {
    private static final jakarta.validation.Validator EMAIL_VALIDATOR =
            jakarta.validation.Validation.buildDefaultValidatorFactory().getValidator();

    private static final class EmailValue {
        @jakarta.validation.constraints.Email
        private String value;
    }

    private final Map<String, Object> input;
    private final Map<String, List<String>> errors = new LinkedHashMap<>();

    public AuthInput(Map<String, Object> input) {
        this.input = input == null ? Map.of() : input;
    }

    public String required(String field, int max, boolean trim, boolean lowercase) {
        Object raw = input.get(field);
        String label = field.replace('_', ' ');
        if (raw == null || (raw instanceof String text && text.trim().isEmpty())) {
            error(field, "The " + label + " field is required.");
            return "";
        }
        if (!(raw instanceof String)) {
            error(field, "The " + label + " field must be a string.");
            return "";
        }
        String value = (String) raw;
        if (trim) value = value.trim();
        if (lowercase) value = value.toLowerCase(Locale.ROOT);
        if (max > 0 && value.codePointCount(0, value.length()) > max) {
            error(field, "The " + label + " field must not be greater than " + max + " characters.");
        }
        return value;
    }

    public String username(boolean registration) {
        String value = required("username", 50, true, true);
        if (registration && !value.isEmpty()) {
            if (value.length() < 3) error("username", "The username field must be at least 3 characters.");
            if (!value.matches("[a-z0-9._]+")) error("username", "The username may only contain lowercase letters, numbers, dots and underscores.");
            if (value.matches("^[._].*|.*[._]$")) error("username", "The username must not start or end with a dot or underscore.");
        }
        return value;
    }

    public String email() {
        String value = required("email", 255, true, true);
        if (!value.isEmpty() && !EMAIL_VALIDATOR.validateValue(EmailValue.class, "value", value).isEmpty()) {
            error("email", "The email field must be a valid email address.");
        }
        return value;
    }

    /** bcrypt silently truncates beyond 72 bytes, so reject absurdly long inputs (not in Laravel rules, cheap safety net). */
    private static final int MAX_PASSWORD_BYTES = 1024;

    public String password(boolean confirmed, boolean strong) {
        String value = required("password", 0, false, false);
        if (confirmed && !value.isEmpty()) {
            if (value.codePointCount(0, value.length()) < 8) error("password", "The password field must be at least 8 characters.");
            if (value.getBytes(java.nio.charset.StandardCharsets.UTF_8).length > MAX_PASSWORD_BYTES) {
                error("password", "The password field is too long.");
            }
            if (!value.equals(input.get("password_confirmation"))) error("password", "The password field confirmation does not match.");
            if (strong && !value.matches("(?s).*[a-zA-Z].*")) error("password", "The password field must contain at least one letter.");
            if (strong && !value.matches("(?s).*[0-9].*")) error("password", "The password field must contain at least one number.");
        }
        return value;
    }

    public void error(String field, String message) {
        errors.computeIfAbsent(field, ignored -> new ArrayList<>()).add(message);
    }

    public void validate() {
        if (!errors.isEmpty()) throw new AuthValidationException(errors);
    }
}
