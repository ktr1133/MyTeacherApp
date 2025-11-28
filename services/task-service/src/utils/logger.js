const winston = require('winston')

const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  defaultMeta: { service: 'task-service' },
  transports: [
    // Always include console output for container environments
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.simple()
      )
    })
  ]
})

// Add file logging only if not in container environment
if (process.env.NODE_ENV !== 'production' && !process.env.CONTAINER_ENV) {
  logger.add(new winston.transports.File({
    filename: 'logs/error.log',
    level: 'error',
    handleExceptions: true
  }))
  logger.add(new winston.transports.File({
    filename: 'logs/combined.log',
    handleExceptions: true
  }))
}

module.exports = logger
