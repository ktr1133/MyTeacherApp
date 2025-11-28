const express = require('express')
const cors = require('cors')
const helmet = require('helmet')
const compression = require('compression')
const morgan = require('morgan')
require('dotenv').config()

const logger = require('./utils/logger')
const healthRoute = require('./routes/health')
const taskRoutes = require('./routes/tasks')
const authMiddleware = require('./middleware/auth')
const errorHandler = require('./middleware/errorHandler')

const app = express()
const PORT = process.env.PORT || 3000

// Security middleware
app.use(helmet())
app.use(cors({
  origin: process.env.API_BASE_URL || 'http://localhost:8080',
  credentials: true
}))

// General middleware
app.use(compression())
app.use(express.json({ limit: '10mb' }))
app.use(express.urlencoded({ extended: true }))

// Logging
if (process.env.NODE_ENV !== 'test') {
  app.use(morgan('combined', { stream: { write: (message) => logger.info(message.trim()) } }))
}

// Routes
app.use('/health', healthRoute)
app.use('/api/tasks', authMiddleware, taskRoutes)

// Root endpoint
app.get('/', (req, res) => {
  res.json({
    service: 'MyTeacher Task Service',
    version: '1.0.0',
    status: 'running',
    timestamp: new Date().toISOString()
  })
})

// Error handling
app.use(errorHandler)

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({
    error: 'Not Found',
    message: `Route ${req.originalUrl} not found`
  })
})

// Start server
if (process.env.NODE_ENV !== 'test') {
  app.listen(PORT, () => {
    logger.info(`Task Service listening on port ${PORT}`)
    logger.info(`Environment: ${process.env.NODE_ENV || 'development'}`)
  })
}

module.exports = app
