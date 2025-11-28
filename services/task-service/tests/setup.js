// Jest setup for Task Service tests
const logger = require('../src/utils/logger')

// Suppress logs during testing
if (process.env.NODE_ENV === 'test') {
  logger.transports.forEach(transport => {
    transport.silent = true
  })
}

// Global test setup
beforeEach(() => {
  // Reset any global state before each test
})

afterEach(() => {
  // Cleanup after each test
})

// Increase timeout for integration tests
jest.setTimeout(30000)
