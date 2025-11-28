const request = require('supertest')
const jwt = require('jsonwebtoken')
const app = require('../../src/index')

describe('Task Routes', () => {
  let authToken

  beforeEach(() => {
    // Create a test JWT token
    authToken = jwt.sign(
      { id: 1, email: 'test@example.com' },
      process.env.JWT_SECRET || 'fallback-secret',
      { expiresIn: '1h' }
    )
  })

  describe('GET /api/tasks', () => {
    test('should return tasks with valid token', async () => {
      const response = await request(app)
        .get('/api/tasks')
        .set('Authorization', `Bearer ${authToken}`)
        .expect(200)

      expect(response.body.success).toBe(true)
      expect(response.body).toHaveProperty('data')
      expect(response.body).toHaveProperty('pagination')
    })

    test('should return 401 without token', async () => {
      const response = await request(app)
        .get('/api/tasks')
        .expect(401)

      expect(response.body.success).toBe(false)
      expect(response.body.error).toBe('Authorization header missing')
    })

    test('should return 401 with invalid token', async () => {
      const response = await request(app)
        .get('/api/tasks')
        .set('Authorization', 'Bearer invalid-token')
        .expect(401)

      expect(response.body.success).toBe(false)
      expect(response.body.error).toBe('Invalid token')
    })
  })

  describe('POST /api/tasks', () => {
    test('should create task with valid data', async () => {
      const taskData = {
        title: 'Test Task',
        description: 'Test Description'
      }

      const response = await request(app)
        .post('/api/tasks')
        .set('Authorization', `Bearer ${authToken}`)
        .send(taskData)
        .expect(201)

      expect(response.body.success).toBe(true)
      expect(response.body.data).toMatchObject({
        title: taskData.title,
        description: taskData.description,
        status: 'pending'
      })
    })

    test('should return 400 without title', async () => {
      const taskData = {
        description: 'Test Description'
      }

      const response = await request(app)
        .post('/api/tasks')
        .set('Authorization', `Bearer ${authToken}`)
        .send(taskData)
        .expect(400)

      expect(response.body.success).toBe(false)
      expect(response.body.error).toBe('Title is required')
    })
  })

  describe('PUT /api/tasks/:id', () => {
    test('should update task with valid data', async () => {
      const updateData = {
        title: 'Updated Task',
        description: 'Updated Description',
        status: 'completed'
      }

      const response = await request(app)
        .put('/api/tasks/1')
        .set('Authorization', `Bearer ${authToken}`)
        .send(updateData)
        .expect(200)

      expect(response.body.success).toBe(true)
      expect(response.body.data).toMatchObject(updateData)
    })
  })

  describe('DELETE /api/tasks/:id', () => {
    test('should delete task', async () => {
      const response = await request(app)
        .delete('/api/tasks/1')
        .set('Authorization', `Bearer ${authToken}`)
        .expect(200)

      expect(response.body.success).toBe(true)
      expect(response.body.message).toBe('Task deleted successfully')
    })
  })
})
