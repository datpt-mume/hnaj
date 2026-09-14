package com.hnaj.api.exception;

/**
 * Mã lỗi ổn định cho luồng authentication — mapping 1-1 với
 * hnaj-be/app/Enums/AuthErrorCode.php.
 * Xem docs/migration/knowledge-base/01-api-contract.md §4.
 */
public enum AuthErrorCode {
    INVALID_CREDENTIALS,         // 401
    EMAIL_NOT_VERIFIED,          // 403
    ACCOUNT_NOT_ACTIVE,          // 403
    INVALID_VERIFICATION_TOKEN,  // 422
    EMAIL_ALREADY_VERIFIED,      // 409
    UNAUTHENTICATED,             // 401
    FORBIDDEN_ROLE,              // 403
    GOOGLE_AUTH_FAILED           // 422
}
