# kraken_with_s3
Optimize image and upload on amazon S3 directly from Kraken

CodeIgniter Library to Optimize images and upload them into S3 bucket using Kraken's PHP API.

How to use it:

1. Load the Library in your controller

2. use the function $this->kraken_lib->optimize_and_upload($file_path, $file_name, $bucket_folder, $resize);

3. That's it, Check the S3 bucket for the kraked file.

You can view the example in the Controller Folder.

