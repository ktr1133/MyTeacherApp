const request = require('supertest')
const app = require('../../src/index')

describe('Health Check', () => {
  test('GET /health should return healthy status', async () => {
    const response = await request(app)
      .get('/health')
      .expect(200)

    expect(response.body).toMatchObject({
      status: 'healthy',
      service: 'task-service',
      version: '1.0.0'
    })

    expect(response.body).toHaveProperty('timestamp')
    expect(response.body).toHaveProperty('checks')
  })

  test('Health check should include system metrics', async () => {
    const response = await request(app)
      .get('/health')
      .expect(200)

    expect(response.body.checks).toHaveProperty('memory')
    expect(response.body.checks).toHaveProperty('uptime')
    expect(typeof response.body.checks.uptime).toBe('number')
  })
})
