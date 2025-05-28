# Postman Runner Setup untuk Testing Panic Button

## Cara 1: Manual Set Token

1. **Login dulu** menggunakan request "Login User/Admin/Relawan"
2. **Copy token** dari response
3. **Set Collection Variable**:
   - Klik Collection "SIGAP API Collection"
   - Go to Variables tab
   - Set nilai untuk `token` variable

## Cara 2: Auto Set Token (Recommended)

Collection sudah diupdate dengan script yang otomatis set token setelah login.

### Langkah Testing dengan Runner:

1. **Import Collection** ke Postman
2. **Set base_url** di Collection Variables kalau perlu
3. **Run Collection** dengan urutan:
   - Login User/Admin/Relawan (untuk set token otomatis)
   - Kirim Panic Report Random (untuk testing dengan data random)

### Untuk Testing 50 Data Sekaligus:

1. **Buka Collection Runner**
2. **Select Collection**: SIGAP API Collection
3. **Uncheck semua** kecuali:
   - Login User/Admin/Relawan
   - Kirim Panic Report Random
4. **Set Iterations**: 50
5. **Set Delay**: 100ms (optional, untuk tidak spam server)
6. **Run Collection**

### Environment Variables yang Tersedia:

- `{{base_url}}` - Base URL API
- `{{token}}` - Bearer token (auto set dari login)
- `{{$randomLatitude}}` - Random latitude (generated per request)
- `{{$randomLongitude}}` - Random longitude (generated per request)
- `{{$randomStreetName}}` - Random street name
- `{{$randomCity}}` - Random city name

## Alternative: Postman Script untuk Bulk Insert

Kalau mau lebih advance, bisa pakai script ini di Tests tab request login:

```javascript
// Set token
if (pm.response.code === 200) {
    const responseJson = pm.response.json();
    pm.collectionVariables.set('token', responseJson.access_token);
    
    // Bulk create panic reports
    const baseUrl = pm.collectionVariables.get('base_url');
    const token = responseJson.access_token;
    
    for (let i = 0; i < 10; i++) {
        const lat = Math.random() * (-6.0 - (-6.4)) + (-6.4);
        const lng = Math.random() * (107.0 - 106.5) + 106.5;
        
        pm.sendRequest({
            url: `${baseUrl}/api/panic`,
            method: 'POST',
            header: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: {
                mode: 'raw',
                raw: JSON.stringify({
                    latitude: lat.toFixed(6),
                    longitude: lng.toFixed(6),
                    location_description: `Emergency location ${i+1}`
                })
            }
        }, function (err, res) {
            if (err) {
                console.log('Error:', err);
            } else {
                console.log('Panic report created:', res.json());
            }
        });
    }
}
```
