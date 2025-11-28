const request = require('supertest')
const app = require('../../src/index')

describe('Integration Tests', () => {
  test('Root endpoint should return service info', async () => {
    const response = await request(app)
      .get('/')
      .expect(200)

    expect(response.body).toMatchObject({
      service: 'MyTeacher Task Service',
      version: '1.0.0',
      status: 'running'
    })

    expect(response.body).toHaveProperty('timestamp')
  })

  test('404 handler should work for unknown routes', async () => {
    const response = await request(app)
      .get('/nonexistent')
      .expect(404)

    expect(response.body).toMatchObject({
      error: 'Not Found',
      message: 'Route /nonexistent not found'
    })
  })

  test('CORS should be properly configured', async () => {
    const response = await request(app)
      .options('/health')
      .expect(204)

    expect(response.headers).toHaveProperty('access-control-allow-origin')
  })
})
