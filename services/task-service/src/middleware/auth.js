const jwt = require('jsonwebtoken')
const logger = require('../utils/logger')

const authMiddleware = (req, res, next) => {
  try {
    const authHeader = req.headers.authorization

    if (!authHeader) {
      return res.status(401).json({
        success: false,
        error: 'Authorization header missing'
      })
    }

    const token = authHeader.split(' ')[1] // Bearer <token>

    if (!token) {
      return res.status(401).json({
        success: false,
        error: 'Token missing'
      })
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'fallback-secret')
    req.user = decoded

    next()
  } catch (error) {
    logger.error('Authentication failed:', error)
    res.status(401).json({
      success: false,
      error: 'Invalid token'
    })
  }
}

module.exports = authMiddleware
