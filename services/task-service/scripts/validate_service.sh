#!/bin/bash
echo "Validate service script executed at $(date)"

# Health check
curl -f http://localhost:3000/health || exit 1

echo "Service validation completed successfully"