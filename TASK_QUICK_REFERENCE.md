# Task Management - Quick Reference

## Access URLs

### For Workers
- **URL:** `/my-tasks`
- **Route Name:** `tasks.my-tasks`
- View assigned tasks, update status, add comments with media

### For Inspectors
- **URL:** `/inspector/tasks`
- **Route Name:** `inspector.tasks`
- Inspect tasks, change status, add comments with media

## Key Features

### Workers Can:
✅ View all assigned tasks  
✅ Start tasks (Pending → In Progress)  
✅ Complete tasks (In Progress → Completed)  
✅ Add comments with text, images, and videos  
✅ View inspector feedback  
✅ Filter tasks by status  

### Inspectors Can:
✅ View all project tasks  
✅ Change any task status  
✅ Inspect completed tasks (Pass/Fail)  
✅ Provide detailed feedback  
✅ Add comments with text, images, and videos  
✅ Filter by status and inspection status  

## Media Upload
- **Supported:** JPG, PNG, GIF, MP4, MOV, AVI
- **Max Size:** 50MB per file
- **Multiple Files:** Yes
- **Storage:** `storage/app/public/task-media/`

## Setup Completed
✅ Migration run successfully  
✅ Storage link created  
✅ Routes registered  
✅ Components created  
✅ Views created  

## Testing

### Test as Worker
```bash
# 1. Ensure you have a worker user with assigned tasks
# 2. Visit: /my-tasks
# 3. Try starting a task
# 4. Add a comment with an image/video
# 5. Complete the task
```

### Test as Inspector
```bash
# 1. Ensure you have an inspector user on a project
# 2. Visit: /inspector/tasks
# 3. Filter by "Completed" status
# 4. Inspect a task (pass or fail)
# 5. Add a comment with media
```

## Troubleshooting

**Can't upload files?**
- Check: `php artisan storage:link` was run
- Verify: `storage/app/public/` is writable
- Check: PHP settings allow 50MB uploads

**Images not displaying?**
- Verify: `APP_URL` is correct in `.env`
- Check: `/storage` link exists in public folder
- Inspect: Browser console for errors

**Permission errors?**
- Verify: User has correct role (worker/inspector)
- Check: User is assigned to the project
- Review: TaskPolicy authorization rules

## Next Steps
1. Test with actual users
2. Verify file uploads work correctly
3. Check notification system (if integrated)
4. Review performance with multiple media files
