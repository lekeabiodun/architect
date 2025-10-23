# Task Management Implementation - Workers & Inspectors

## Overview
This document outlines the implementation of the task management system for workers and inspectors, including media upload capabilities (images and videos) for progress tracking and documentation.

## Features Implemented

### 1. Worker Features
Workers can now:
- ✅ View all tasks assigned to them
- ✅ Update task status (Pending → In Progress → Completed)
- ✅ Add comments with text
- ✅ Upload images and videos to show progress
- ✅ View inspector feedback on failed inspections
- ✅ See all comments and media from team members
- ✅ Filter tasks by status

**Route:** `/my-tasks`
**Component:** `App\Livewire\Task\WorkerView`
**View:** `resources/views/livewire/task/worker-view.blade.php`

### 2. Inspector Features
Inspectors can now:
- ✅ View all tasks in projects they're assigned to
- ✅ Change task status
- ✅ Add comments with text
- ✅ Upload images and videos for documentation
- ✅ Inspect completed tasks (Pass/Fail)
- ✅ Provide detailed inspection feedback
- ✅ View worker updates and media uploads
- ✅ Filter by task status and inspection status

**Route:** `/inspector/tasks`
**Component:** `App\Livewire\Task\InspectorView`
**View:** `resources/views/livewire/task/inspector-view.blade.php`

## Database Changes

### Migration: `add_media_fields_to_task_comments_table`
Added to `task_comments` table:
- `media_files` (JSON) - Stores array of uploaded files with metadata
- `media_type` (String) - Indicates type: 'image', 'video', or 'mixed'

**File:** `database/migrations/2025_10_23_173222_add_media_fields_to_task_comments_table.php`

## Updated Models

### TaskComment Model
Added support for media files:
- `media_files` and `media_type` added to fillable array
- `media_files` cast as array for automatic JSON handling

**File:** `app/Models/TaskComment.php`

## Media Upload Specifications

### Supported File Types
- **Images:** JPG, JPEG, PNG, GIF
- **Videos:** MP4, MOV, AVI
- **Maximum Size:** 50MB per file
- **Multiple Files:** Yes, users can upload multiple files per comment

### Storage
- Files are stored in: `storage/app/public/task-media/`
- Accessible via: `Storage::url($media['path'])`
- Make sure to run: `php artisan storage:link`

### Media Structure
Each uploaded file is stored with the following metadata:
```php
[
    'path' => 'task-media/filename.jpg',
    'name' => 'original-filename.jpg',
    'mime_type' => 'image/jpeg',
    'size' => 1024000
]
```

## User Interface Features

### Worker View
1. **Statistics Dashboard**
   - Total tasks assigned
   - Pending, In Progress, Completed counts

2. **Task Cards**
   - Task information and status badges
   - Inspector feedback display (for failed inspections)
   - Comments section with media display
   - Action buttons (Start, Complete, Add Comment, View Details)

3. **Modals**
   - Add Comment with file upload
   - View task details

### Inspector View
1. **Statistics Dashboard**
   - Total tasks in projects
   - Pending inspection count
   - Passed and Failed counts

2. **Task Cards**
   - Full task information
   - Assigned worker details
   - Previous inspection feedback
   - Comments and media from workers
   - Action buttons (Inspect, Change Status, Add Comment, View Details)

3. **Modals**
   - Inspect Task (Pass/Fail with feedback)
   - Change Status
   - Add Comment with file upload
   - View task details

## Authorization

### Worker Permissions
- Can view tasks assigned to them (`TaskPolicy::view`)
- Can update status of their own tasks (`TaskPolicy::complete`)
- Can add comments to their tasks (`TaskPolicy::view`)

### Inspector Permissions
- Must have `inspect tasks` permission or `inspector` role
- Can view tasks in projects they're assigned to (`TaskPolicy::inspect`)
- Can change task status (`TaskPolicy::inspect`)
- Can perform inspections and provide feedback (`TaskPolicy::inspect`)
- Can add comments with media (`TaskPolicy::inspect`)

## Task Status Flow

### Worker Actions
1. **Pending** → Click "Start Task" → **In Progress**
2. **In Progress** → Click "Complete Task" → **Completed** (triggers inspection status to 'pending')

### Inspector Actions
- Can change status to: Pending, In Progress, or Completed
- Can inspect completed tasks:
  - **Pass Inspection** → Sets `inspection_status` to 'passed'
  - **Fail Inspection** → Sets `inspection_status` to 'failed', worker sees feedback

## Display of Media Files

### Images
- Displayed in a 2-column grid
- Thumbnail view (h-32)
- Hover effect with magnifying glass icon
- Click to open full size in new tab

### Videos
- Displayed in a 2-column grid
- Native HTML5 video player with controls
- Inline playback

## Testing the Implementation

### For Workers
1. Log in as a worker user
2. Navigate to `/my-tasks`
3. Start a task and add a comment with images/videos
4. Complete the task

### For Inspectors
1. Log in as an inspector user
2. Navigate to `/inspector/tasks`
3. Filter by "Completed" and "Pending Inspection"
4. Inspect a task and provide feedback
5. Add comments with media

## Dependencies
- **Livewire 3.x** - For reactive components
- **Livewire WithFileUploads** - For handling file uploads
- **Laravel Storage** - For file storage management
- **Flux UI** - For UI components

## Configuration

Make sure your `.env` file has:
```env
FILESYSTEM_DISK=public
```

Run the storage link command:
```bash
php artisan storage:link
```

## Future Enhancements

Possible improvements:
- [ ] Image compression before upload
- [ ] Video thumbnail generation
- [ ] Bulk file download
- [ ] File deletion capability
- [ ] Real-time notifications for inspectors when tasks are completed
- [ ] Real-time notifications for workers when inspections are completed
- [ ] Media gallery view
- [ ] File size progress indicator during upload

## Files Modified/Created

### Created Files
1. `app/Livewire/Task/InspectorView.php`
2. `resources/views/livewire/task/inspector-view.blade.php`
3. `database/migrations/2025_10_23_173222_add_media_fields_to_task_comments_table.php`

### Modified Files
1. `app/Livewire/Task/WorkerView.php` - Added file upload functionality
2. `resources/views/livewire/task/worker-view.blade.php` - Added media upload UI and display
3. `app/Models/TaskComment.php` - Added media fields support
4. `routes/web.php` - Added inspector tasks route

## Security Considerations

1. **File Validation**
   - File type validation (mimes)
   - File size limit (50MB max)
   - Only authenticated users can upload

2. **Authorization**
   - Workers can only manage their own tasks
   - Inspectors must be assigned to project
   - Proper policy checks on all actions

3. **Storage**
   - Files stored in public disk
   - Unique filenames generated by Laravel
   - No direct file deletion from frontend

## Troubleshooting

### Upload Issues
- Check storage permissions: `storage/app/public/` should be writable
- Verify symbolic link: `public/storage` → `storage/app/public`
- Check PHP upload limits in `php.ini`:
  - `upload_max_filesize = 50M`
  - `post_max_size = 50M`

### Display Issues
- Verify `APP_URL` is set correctly in `.env`
- Check that files exist in `storage/app/public/task-media/`
- Inspect browser console for 404 errors

## Conclusion

The task management system now provides comprehensive functionality for workers to document their progress and for inspectors to review and provide feedback, all with rich media support for better communication and documentation.
