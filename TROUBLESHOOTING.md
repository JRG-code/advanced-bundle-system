# Troubleshooting Parse Error - Version 1.1.9

## The Problem
You're seeing: `syntax error, unexpected identifier "ABS_PLUGIN_DIR", expecting ")"` on line 26

## What's Wrong
The file `advanced-bundle-system.php` is getting corrupted during upload to the server. The local file is correct, but something in the upload process is breaking it.

## Solution Steps

### Option 1: Clear All Caches First
1. **Disable PHP OPcache** (if using Hostinger, do this in PHP Config)
2. **Clear WordPress cache** (if using any cache plugin)
3. **Clear browser cache**
4. Try reactivating the plugin

### Option 2: Manual File Upload via FTP/File Manager
1. **Download the plugin** as ZIP from GitHub
2. **Extract it locally** on your computer
3. **Delete the entire plugin folder** from server: `/wp-content/plugins/advanced-bundle-system/`
4. **Upload the fresh extracted folder** via FTP or Hostinger File Manager
5. Make sure to upload in **BINARY mode**, not ASCII mode
6. Activate the plugin

### Option 3: Check File Encoding on Server
If you have SSH access:
```bash
cd /home/u125521932/domains/thecouplesbrand.com/public_html/wp-content/plugins/advanced-bundle-system/
file advanced-bundle-system.php
# Should say: PHP script, ASCII text

# Check for hidden characters:
cat -A advanced-bundle-system.php | head -30
```

### Option 4: Replace Just the Main File
1. Download **only** `advanced-bundle-system.php` from GitHub
2. Go to Hostinger File Manager
3. Navigate to `/wp-content/plugins/advanced-bundle-system/`
4. **Delete** the old `advanced-bundle-system.php`
5. **Upload** the new clean file
6. Make sure file permissions are `644`

## Common Causes
- **OPcache** caching old/corrupted version
- **FTP upload in ASCII mode** changing line endings
- **Copy-paste** from web browser introducing special characters
- **Windows notepad** adding BOM or wrong line endings
- **Direct editing in WordPress/cPanel** adding invisible characters

## Test the File Locally First
If you have PHP CLI:
```bash
php -l advanced-bundle-system.php
```
Should say: "No syntax errors detected"

## Need Help?
If none of this works, the issue might be:
- Server PHP version incompatibility
- Modified file on server that needs to be completely removed
- File permissions issue
