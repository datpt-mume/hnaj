package com.hnaj.auth.validation;

import java.util.List;
import java.util.Map;

public final class AuthValidationException extends RuntimeException {
    private final Map<String, List<String>> errors;

    public AuthValidationException(Map<String, List<String>> errors) {
        super("The given data was invalid.");
        this.errors = errors;
    }

    public Map<String, List<String>> getErrors() {
        return errors;
    }
}
