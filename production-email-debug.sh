#!/bin/bash

# Production Email Debug Script for DigitalOcean
echo "🔍 SIGAP API - Production Email Debug"
echo "====================================="

# Test email sending directly via artisan tinker
echo "📧 Testing direct email sending..."

curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/email/send-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "7ddsm@punkproof.com"}' \
  -v

echo ""
echo "📋 Response received above"
echo ""

# Test manual OTP verification (if you have the code)
echo "🔢 To verify OTP, use this command:"
echo "curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/email/verify-otp \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"email\": \"7ddsm@punkproof.com\", \"otp\": \"XXXXXX\"}'"

echo ""
echo "🎯 Troubleshooting steps for DigitalOcean:"
echo "1. Check if queue worker is running: sudo systemctl status laravel-worker"
echo "2. Check Laravel logs: tail -f /var/www/html/storage/logs/laravel.log"
echo "3. Test SMTP connection: telnet smtp.gmail.com 587"
echo "4. Check DigitalOcean firewall allows port 587 outbound"
echo "5. Verify Gmail App Password is correct"
echo "6. Check if emails are in spam folder"
