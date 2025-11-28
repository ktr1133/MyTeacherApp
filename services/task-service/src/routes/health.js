const express = require('express')
const router = express.Router()
const logger = require('../utils/logger')

// Health check endpoint
router.get('/', async (req, res) => {
  try {
    const healthData = {
      status: 'healthy',
      timestamp: new Date().toISOString(),
      service: 'task-service',
      version: '1.0.0',
      checks: {
        database: 'ok', // TODO: Implement actual DB check
        redis: 'ok', // TODO: Implement actual Redis check
        memory: process.memoryUsage(),
        uptime: process.uptime()
      }
    }

    res.status(200).json(healthData)
  } catch (error) {
    logger.error('Health check failed:', error)
    res.status(500).json({
      status: 'unhealthy',
      timestamp: new Date().toISOString(),
      error: error.message
    })
  }
})

module.exports = router
