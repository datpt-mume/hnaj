package com.hnaj.api.exception;

public class VisitException extends RuntimeException {

    private final VisitErrorCode errorCode;
    private final int status;

    public VisitException(VisitErrorCode errorCode, String message, int status) {
        super(message);
        this.errorCode = errorCode;
        this.status = status;
    }

    public VisitErrorCode getErrorCode() {
        return errorCode;
    }

    public int getStatus() {
        return status;
    }
}
