# 📸 Image Upload Specification - Laporan dengan Foto

## Current Configuration

### ✅ Allowed Image Formats

```
JPG, JPEG, PNG, BMP, GIF, SVG, WEBP, HEIC, HEIF
```

**Laravel validation rule:** `mimes:jpeg,jpg,png,gif,bmp,webp,heic,heif,svg`

> 📱 **iPhone Support:** HEIC and HEIF formats are now supported for photos taken with iPhone devices.

### 📏 Size Limits

| Parameter              | Value                 | Note          |
| ---------------------- | --------------------- | ------------- |
| **Maximum File Size**  | **20 MB** (20,480 KB) | Hard limit    |
| **Minimum File Size**  | No limit              | -             |
| **Maximum Dimensions** | No limit              | Not validated |
| **Minimum Dimensions** | No limit              | Not validated |
| **Aspect Ratio**       | Any                   | Not validated |

### 🔧 Technical Details

| Setting         | Value                                                                |
| --------------- | -------------------------------------------------------------------- |
| Validation Rule | `required\|mimes:jpeg,jpg,png,gif,bmp,webp,heic,heif,svg\|max:20480` |
| Storage Path    | `storage/app/public/reports/`                                        |
| Public URL      | `/storage/reports/{filename}`                                        |
| Required        | ✅ Yes                                                               |

---

## API Endpoint

### Upload Photo

**Endpoint:** `POST /api/reports/upload-photo`

**Headers:**

```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
```

**Request Body:**

```
photo: (file) - Image file (required)
```

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Foto berhasil diupload",
  "photo_path": "reports/abc123.jpg",
  "photo_url": "https://sigap-api-5hk6r.ondigitalocean.app/storage/reports/abc123.jpg"
}
```

**Error Response (422 Validation Error):**

```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "photo": ["The photo must be a file of type: jpeg, jpg, png, gif, bmp, webp, heic, heif, svg.", "The photo must not be greater than 20480 kilobytes."]
  }
}
```

---

## Frontend Implementation Guide

### React/Next.js Example

```tsx
import { useState } from "react";

export default function ReportPhotoUpload() {
  const [photo, setPhoto] = useState<File | null>(null);
  const [preview, setPreview] = useState<string>("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];

    if (!file) return;

    // Validate file type (including iPhone HEIC/HEIF)
    const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp", "image/heic", "image/heif", "image/bmp", "image/svg+xml"];
    if (!validTypes.includes(file.type)) {
      setError("Format file tidak valid. Gunakan JPG, PNG, GIF, WEBP, HEIC, HEIF, BMP, atau SVG.");
      return;
    }

    // Validate file size (20 MB)
    const maxSize = 20 * 1024 * 1024; // 20 MB in bytes
    if (file.size > maxSize) {
      setError("Ukuran file terlalu besar. Maksimal 20 MB.");
      return;
    }

    setError("");
    setPhoto(file);

    // Create preview
    const reader = new FileReader();
    reader.onloadend = () => {
      setPreview(reader.result as string);
    };
    reader.readAsDataURL(file);
  };

  const handleUpload = async () => {
    if (!photo) return;

    setLoading(true);
    setError("");

    const formData = new FormData();
    formData.append("photo", photo);

    try {
      const response = await fetch("https://sigap-api-5hk6r.ondigitalocean.app/api/reports/upload-photo", {
        method: "POST",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("access_token")}`,
        },
        body: formData,
      });

      const data = await response.json();

      if (response.ok) {
        console.log("Upload success:", data);
        // Save photo_path for later use when creating report
        return data.photo_path;
      } else {
        setError(data.message || "Upload gagal");
      }
    } catch (err) {
      setError("Terjadi kesalahan jaringan. Silakan coba lagi.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h2>Upload Foto Laporan</h2>

      {error && <div className="alert alert-error">{error}</div>}

      <input type="file" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onChange={handleFileChange} />

      {preview && (
        <div>
          <img src={preview} alt="Preview" style={{ maxWidth: "300px" }} />
          <p>Ukuran: {(photo!.size / 1024 / 1024).toFixed(2)} MB</p>
        </div>
      )}

      <button onClick={handleUpload} disabled={!photo || loading}>
        {loading ? "Mengupload..." : "Upload Foto"}
      </button>
    </div>
  );
}
```

### JavaScript/HTML Example

```html
<input type="file" id="photoInput" accept="image/*" />
<img id="preview" style="max-width: 300px; display: none;" />
<button id="uploadBtn">Upload Photo</button>

<script>
  const photoInput = document.getElementById("photoInput");
  const preview = document.getElementById("preview");
  const uploadBtn = document.getElementById("uploadBtn");

  photoInput.addEventListener("change", (e) => {
    const file = e.target.files[0];

    if (!file) return;

    // Validate file size
    const maxSize = 20 * 1024 * 1024; // 20 MB
    if (file.size > maxSize) {
      alert("File terlalu besar! Maksimal 20 MB");
      photoInput.value = "";
      return;
    }

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = "block";
    };
    reader.readAsDataURL(file);
  });

  uploadBtn.addEventListener("click", async () => {
    const file = photoInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("photo", file);

    try {
      const response = await fetch("https://sigap-api-5hk6r.ondigitalocean.app/api/reports/upload-photo", {
        method: "POST",
        headers: {
          Authorization: "Bearer " + localStorage.getItem("access_token"),
        },
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        console.log("Photo uploaded:", data.photo_path);
        alert("Foto berhasil diupload!");
      } else {
        alert("Upload gagal: " + data.message);
      }
    } catch (error) {
      alert("Terjadi kesalahan jaringan");
    }
  });
</script>
```

---

## Validation Rules Summary

### Client-Side (Recommended)

```javascript
// File type validation
const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp", "image/heic", "image/heif"];

// File size validation (20 MB)
const maxSizeBytes = 20 * 1024 * 1024; // 20,971,520 bytes

// Validation function
function validateImage(file) {
  if (!validTypes.includes(file.type)) {
    return { valid: false, error: "Format file tidak valid" };
  }

  if (file.size > maxSizeBytes) {
    return { valid: false, error: "Ukuran file terlalu besar. Maksimal 20 MB" };
  }

  return { valid: true };
}
```

### Server-Side (Automatic)

Laravel automatically validates:

- ✅ File must be one of: JPEG, JPG, PNG, GIF, BMP, WEBP, HEIC, HEIF, SVG
- ✅ File size ≤ 20 MB (20480 KB)
- ✅ File must be present (required)

---

## Recommended Frontend Improvements

### 1. Image Compression (Before Upload)

Use library like `browser-image-compression`:

```javascript
import imageCompression from "browser-image-compression";

async function compressImage(file) {
  const options = {
    maxSizeMB: 18, // Compress to max 18 MB (leave buffer for 20 MB limit)
    maxWidthOrHeight: 3840, // Max dimension (4K resolution)
    useWebWorker: true,
  };

  try {
    const compressedFile = await imageCompression(file, options);
    return compressedFile;
  } catch (error) {
    console.error("Compression error:", error);
    return file;
  }
}
```

### 2. Image Preview with Dimensions

```javascript
function getImageDimensions(file) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      resolve({ width: img.width, height: img.height });
    };
    img.src = URL.createObjectURL(file);
  });
}

// Usage
const dimensions = await getImageDimensions(file);
console.log(`Image size: ${dimensions.width}x${dimensions.height}`);
```

### 3. Progress Bar

```javascript
const xhr = new XMLHttpRequest();

xhr.upload.addEventListener("progress", (e) => {
  if (e.lengthComputable) {
    const percentComplete = (e.loaded / e.total) * 100;
    console.log(`Upload progress: ${percentComplete}%`);
  }
});

xhr.open("POST", "/api/reports/upload-photo");
xhr.setRequestHeader("Authorization", "Bearer " + token);
xhr.send(formData);
```

---

## Error Messages

| Error          | Message (ID)                       | Message (EN)                    |
| -------------- | ---------------------------------- | ------------------------------- |
| Invalid format | Format file tidak valid            | Invalid file format             |
| Too large      | File terlalu besar. Maksimal 5 MB  | File too large. Max 5 MB        |
| No file        | Pilih file terlebih dahulu         | Please select a file            |
| Network error  | Gagal upload. Cek koneksi internet | Upload failed. Check connection |

---

## Testing

### Test Cases

1. ✅ Upload JPG < 20 MB → Success
2. ✅ Upload PNG < 20 MB → Success
3. ✅ Upload WEBP < 20 MB → Success
4. ✅ Upload HEIC from iPhone < 20 MB → Success
5. ✅ Upload HEIF from iPhone < 20 MB → Success
6. ❌ Upload PDF → Error (not valid image format)
7. ❌ Upload 25 MB image → Error (too large)
8. ❌ No file → Error (required)

### cURL Test

```bash
# Test upload
curl -X POST https://sigap-api-5hk6r.ondigitalocean.app/api/reports/upload-photo \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "photo=@/path/to/image.jpg"
```

---

## FAQs

### Q: Apakah bisa upload foto dari iPhone (HEIC/HEIF)?

**A:** Ya! Sekarang sudah support format HEIC dan HEIF dari iPhone.

### Q: Format apa yang paling direkomendasikan?

**A:** JPG/JPEG untuk foto umum, PNG jika butuh transparansi, WEBP untuk ukuran kecil dengan kualitas tinggi, HEIC/HEIF untuk foto dari iPhone (otomatis terkompresi dengan baik).

### Q: Apakah perlu compress image di client?

**A:** Direkomendasikan untuk foto yang sangat besar (>10 MB), tapi dengan limit 20 MB seharusnya sudah cukup untuk kebanyakan foto smartphone.

### Q: Bagaimana cara resize otomatis di backend?

**A:** Saat ini tidak ada. Perlu tambahkan library seperti Intervention Image jika diperlukan.

### Q: Apakah support upload multiple images?

**A:** Tidak. Satu laporan = satu foto.

### Q: Ukuran file maksimal berapa?

**A:** 20 MB (20,480 KB). Cukup untuk foto resolusi tinggi dari smartphone modern.

---

**Last Updated:** January 2025  
**API Version:** 1.0  
**Max File Size:** 20 MB  
**Supported Formats:** JPG, JPEG, PNG, GIF, BMP, WEBP, HEIC, HEIF, SVG
