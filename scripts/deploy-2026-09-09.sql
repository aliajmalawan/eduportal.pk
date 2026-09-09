-- Phase 2: repoint the Madiha Hassan testimonial from an 82 MB .mov (which
-- Chrome and Firefox handle poorly) to the 10.1 MB .mp4 already on disk.
-- Run once on production, then delete the .mov from assets/video-testimonials/.
UPDATE ep_video_testimonials
   SET video_file_path = 'assets/video-testimonials/madiha-hassan-20260613062322.mp4'
 WHERE video_file_path = 'assets/video-testimonials/madiha-hassan-20260613062322.mov';
