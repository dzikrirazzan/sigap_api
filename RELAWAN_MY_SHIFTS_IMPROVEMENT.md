# Improved Relawan My Shifts Endpoint

## Overview

The `/relawan/my-shifts` endpoint has been redesigned to provide a more user-friendly experience for relawan to view their work schedule. The new implementation focuses on providing a clear weekly view with intuitive navigation.

## Endpoint Details

**URL:** `GET /api/relawan/my-shifts`  
**Authentication:** Required (Bearer Token)  
**Role:** Relawan only

## Parameters

### Query Parameters

| Parameter | Type    | Required | Default | Description                                                                          |
| --------- | ------- | -------- | ------- | ------------------------------------------------------------------------------------ |
| `week`    | integer | No       | `0`     | Week offset from current week. `-1` = last week, `0` = current week, `1` = next week |

### Examples

- Current week: `/api/relawan/my-shifts`
- Last week: `/api/relawan/my-shifts?week=-1`
- Next week: `/api/relawan/my-shifts?week=1`
- 2 weeks ago: `/api/relawan/my-shifts?week=-2`

## Response Structure

```json
{
  "relawan": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "week_info": {
    "week_offset": 0,
    "start_date": "2024-01-15",
    "end_date": "2024-01-21",
    "week_label": "Minggu Ini",
    "period_formatted": "15 Jan - 21 Jan 2024"
  },
  "today_status": {
    "date": "2024-01-17",
    "is_on_duty": true,
    "day_name": "Rabu",
    "shift_source": "weekly_pattern"
  },
  "weekly_schedule": [
    {
      "date": "2024-01-15",
      "day_of_week": "monday",
      "day_name": "Senin",
      "date_formatted": "15 Jan 2024",
      "is_today": false,
      "is_past": true,
      "is_future": false,
      "is_scheduled": true,
      "shift_source": "actual_shift",
      "shift_id": 123,
      "has_actual_shift": true,
      "has_pattern": false
    },
    {
      "date": "2024-01-16",
      "day_of_week": "tuesday",
      "day_name": "Selasa",
      "date_formatted": "16 Jan 2024",
      "is_today": false,
      "is_past": true,
      "is_future": false,
      "is_scheduled": false,
      "shift_source": null,
      "shift_id": null,
      "has_actual_shift": false,
      "has_pattern": false
    }
    // ... more days
  ],
  "upcoming_shifts": [
    {
      "date": "2024-01-18",
      "day_name": "Kamis",
      "is_scheduled": true,
      "shift_source": "weekly_pattern"
    }
  ],
  "summary": {
    "total_scheduled_days": 3,
    "days_with_actual_shifts": 1,
    "days_with_patterns_only": 2,
    "work_days_this_week": "3/7 hari"
  },
  "navigation": {
    "previous_week": -1,
    "current_week": 0,
    "next_week": 1
  }
}
```

## Key Improvements

### 1. **Simplified Parameters**

- Replaced complex `start_date` and `end_date` with simple `week` offset
- Default behavior shows current week without any parameters
- Easy navigation: just increment/decrement week number

### 2. **Clear Weekly View**

- Always shows Monday to Sunday (full week)
- Each day includes comprehensive status information
- Clear indication of today vs past/future days

### 3. **Enhanced Day Information**

Each day in `weekly_schedule` provides:

- **Date information**: Raw date, formatted date, day name in Indonesian
- **Status flags**: `is_today`, `is_past`, `is_future`, `is_scheduled`
- **Shift source**: Whether shift comes from actual_shift or weekly_pattern
- **Availability flags**: `has_actual_shift`, `has_pattern`

### 4. **Today's Status**

Quick access to today's duty status without scanning the full week array.

### 5. **Upcoming Shifts**

Shows next 3 upcoming scheduled days for quick reference.

### 6. **Navigation Helper**

Provides week offset values for easy navigation in frontend applications.

### 7. **Comprehensive Summary**

- Total scheduled days in the week
- Breakdown of actual shifts vs pattern-based shifts
- Work days ratio (e.g., "3/7 hari")

## Usage Examples

### Mobile App Integration

```javascript
// Get current week
const response = await fetch("/api/relawan/my-shifts", {
  headers: { Authorization: `Bearer ${token}` },
});

// Check if on duty today
const data = await response.json();
if (data.today_status.is_on_duty) {
  showDutyNotification();
}

// Navigate to next week
const nextWeek = data.navigation.next_week;
const nextWeekResponse = await fetch(`/api/relawan/my-shifts?week=${nextWeek}`);
```

### Frontend Calendar Integration

```javascript
// Build calendar view
data.weekly_schedule.forEach((day) => {
  const calendarCell = document.getElementById(`day-${day.date}`);

  if (day.is_scheduled) {
    calendarCell.classList.add("scheduled");
    calendarCell.setAttribute("data-source", day.shift_source);
  }

  if (day.is_today) {
    calendarCell.classList.add("today");
  }
});
```

## Error Responses

### 403 Forbidden

```json
{
  "message": "Access denied. Only relawan can access this endpoint."
}
```

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

## Backward Compatibility

The old `start_date` and `end_date` parameters are no longer supported. Frontend applications should migrate to use the new `week` parameter for better UX.

## Testing with Postman

The updated Postman collection includes three test cases:

1. **Current Week**: `/relawan/my-shifts` (no parameters)
2. **Last Week**: `/relawan/my-shifts?week=-1`
3. **Next Week**: `/relawan/my-shifts?week=1`

Each request includes proper authentication headers and descriptions for easy testing.
