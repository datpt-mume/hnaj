package com.hnaj.api.common;

import com.hnaj.api.exception.AuthFlowException;
import com.hnaj.api.exception.BookmarkException;
import com.hnaj.api.exception.VisitException;
import jakarta.validation.ConstraintViolationException;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.security.core.AuthenticationException;
import org.springframework.validation.FieldError;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;
import org.springframework.web.servlet.NoHandlerFoundException;

import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Global exception handler — ánh xạ 1-1 theo bảng trong
 * docs/migration/knowledge-base/01-api-contract.md §3.
 */
@RestControllerAdvice
public class ApiExceptionHandler {

    @ExceptionHandler(com.hnaj.auth.validation.AuthValidationException.class)
    public ResponseEntity<Map<String, Object>> handleAuthValidation(
            com.hnaj.auth.validation.AuthValidationException ex) {
        Map<String, Object> errors = new LinkedHashMap<>();
        errors.putAll(ex.getErrors());
        return ApiResponse.error(ex.getMessage(), errors, "VALIDATION_ERROR", HttpStatus.UNPROCESSABLE_ENTITY);
    }

    @ExceptionHandler(org.springframework.http.converter.HttpMessageNotReadableException.class)
    public ResponseEntity<Map<String, Object>> handleUnreadableBody() {
        return ApiResponse.error("The given data was invalid.",
                Map.of("body", List.of("The request body must be a valid JSON object.")),
                "VALIDATION_ERROR", HttpStatus.UNPROCESSABLE_ENTITY);
    }

    @ExceptionHandler(AuthFlowException.class)
    public ResponseEntity<Map<String, Object>> handleAuthFlow(AuthFlowException ex) {
        return ApiResponse.error(ex.getMessage(), null, ex.getErrorCode().name(), HttpStatus.valueOf(ex.getStatus()));
    }

    @ExceptionHandler(BookmarkException.class)
    public ResponseEntity<Map<String, Object>> handleBookmark(BookmarkException ex) {
        return ApiResponse.error(ex.getMessage(), null, ex.getErrorCode().name(), HttpStatus.valueOf(ex.getStatus()));
    }

    @ExceptionHandler(VisitException.class)
    public ResponseEntity<Map<String, Object>> handleVisit(VisitException ex) {
        return ApiResponse.error(ex.getMessage(), null, ex.getErrorCode().name(), HttpStatus.valueOf(ex.getStatus()));
    }

    @ExceptionHandler(AuthenticationException.class)
    public ResponseEntity<Map<String, Object>> handleAuthentication() {
        return ApiResponse.error(
                "Authentication is required to access this resource.",
                null,
                "UNAUTHENTICATED",
                HttpStatus.UNAUTHORIZED);
    }

    @ExceptionHandler(AccessDeniedException.class)
    public ResponseEntity<Map<String, Object>> handleAccessDenied() {
        return ApiResponse.error(
                "You do not have permission to access this resource.",
                null,
                "FORBIDDEN_ROLE",
                HttpStatus.FORBIDDEN);
    }

    @ExceptionHandler(MethodArgumentNotValidException.class)
    public ResponseEntity<Map<String, Object>> handleValidation(MethodArgumentNotValidException ex) {
        Map<String, Object> errors = new LinkedHashMap<>();
        for (FieldError field : ex.getBindingResult().getFieldErrors()) {
            ((java.util.List<String>) errors.computeIfAbsent(
                    field.getField(), k -> new java.util.ArrayList<String>()))
                    .add(field.getDefaultMessage());
        }
        return ApiResponse.error(
                "The given data was invalid.",
                errors,
                "VALIDATION_ERROR",
                HttpStatus.UNPROCESSABLE_ENTITY);
    }

    @ExceptionHandler(ConstraintViolationException.class)
    public ResponseEntity<Map<String, Object>> handleConstraint(ConstraintViolationException ex) {
        Map<String, Object> errors = new LinkedHashMap<>();
        ex.getConstraintViolations().forEach(v -> {
            String path = v.getPropertyPath().toString();
            ((java.util.List<String>) errors.computeIfAbsent(
                    path, k -> new java.util.ArrayList<String>()))
                    .add(v.getMessage());
        });
        return ApiResponse.error(
                "The given data was invalid.",
                errors,
                "VALIDATION_ERROR",
                HttpStatus.UNPROCESSABLE_ENTITY);
    }

    @ExceptionHandler(NoHandlerFoundException.class)
    public ResponseEntity<Map<String, Object>> handleNotFound() {
        return ApiResponse.error(
                "The requested resource was not found.",
                null,
                "NOT_FOUND",
                HttpStatus.NOT_FOUND);
    }

    @ExceptionHandler(Throwable.class)
    public ResponseEntity<Map<String, Object>> handleFallback(Throwable ex) {
        // KHÔNG lộ stack trace / message nội bộ cho client (AGENTS.md §10)
        return ApiResponse.error(
                "An unexpected error occurred.",
                null,
                "INTERNAL_SERVER_ERROR",
                HttpStatus.INTERNAL_SERVER_ERROR);
    }
}
