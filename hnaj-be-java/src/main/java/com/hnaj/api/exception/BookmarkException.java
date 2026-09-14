package com.hnaj.api.exception;

public class BookmarkException extends RuntimeException {

    private final BookmarkErrorCode errorCode;
    private final int status;

    public BookmarkException(BookmarkErrorCode errorCode, String message, int status) {
        super(message);
        this.errorCode = errorCode;
        this.status = status;
    }

    public BookmarkErrorCode getErrorCode() {
        return errorCode;
    }

    public int getStatus() {
        return status;
    }
}
