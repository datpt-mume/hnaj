package com.hnaj.api.exception;

/**
 * Lỗi nghiệp vụ luồng authentication. Mang sẵn HTTP status + error code
 * để ApiExceptionHandler dựng envelope chung.
 */
public class AuthFlowException extends RuntimeException {

    private final AuthErrorCode errorCode;
    private final int status;

    public AuthFlowException(AuthErrorCode errorCode, String message, int status) {
        super(message);
        this.errorCode = errorCode;
        this.status = status;
    }

    public AuthErrorCode getErrorCode() {
        return errorCode;
    }

    public int getStatus() {
        return status;
    }

    // -------- Factory methods mapping Laravel AuthFlowException --------

    public static AuthFlowException invalidCredentials() {
        return new AuthFlowException(AuthErrorCode.INVALID_CREDENTIALS,
                "The provided credentials are incorrect.", 401);
    }

    public static AuthFlowException emailNotVerified() {
        return new AuthFlowException(AuthErrorCode.EMAIL_NOT_VERIFIED,
                "This email address has not been verified yet.", 403);
    }

    public static AuthFlowException accountNotActive() {
        return new AuthFlowException(AuthErrorCode.ACCOUNT_NOT_ACTIVE,
                "This account is not active.", 403);
    }

    public static AuthFlowException invalidVerificationToken() {
        return new AuthFlowException(AuthErrorCode.INVALID_VERIFICATION_TOKEN,
                "This verification link is invalid or has expired.", 422);
    }

    public static AuthFlowException emailAlreadyVerified() {
        return new AuthFlowException(AuthErrorCode.EMAIL_ALREADY_VERIFIED,
                "This email address has already been verified.", 409);
    }

    public static AuthFlowException forbiddenRole() {
        return new AuthFlowException(AuthErrorCode.FORBIDDEN_ROLE,
                "You do not have permission to access this resource.", 403);
    }

    public static AuthFlowException googleAuthFailed(String message) {
        return new AuthFlowException(AuthErrorCode.GOOGLE_AUTH_FAILED, message, 422);
    }

    public static AuthFlowException googleAuthFailed() {
        return googleAuthFailed("Unable to complete Google sign-in.");
    }
}
