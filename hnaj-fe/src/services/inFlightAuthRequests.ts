import { exchangeGoogleCode, verifyEmail } from './authService'

const googleExchangeRequests = new Map<PromiseKey, ReturnType<typeof exchangeGoogleCode>>()
const emailVerificationRequests = new Map<PromiseKey, ReturnType<typeof verifyEmail>>()

type PromiseKey = string

function once<T>(
  requests: Map<PromiseKey, Promise<T>>,
  key: PromiseKey,
  request: () => Promise<T>,
): Promise<T> {
  const existing = requests.get(key)

  if (existing) return existing

  const promise = request().finally(() => {
    requests.delete(key)
  })

  requests.set(key, promise)

  return promise
}

export function exchangeGoogleCodeOnce(code: string) {
  return once(googleExchangeRequests, code, () => exchangeGoogleCode(code))
}

export function verifyEmailOnce(token: string) {
  return once(emailVerificationRequests, token, () => verifyEmail(token))
}
