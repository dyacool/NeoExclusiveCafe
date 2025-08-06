-- Add columns to track removed images
ALTER TABLE product_images 
ADD COLUMN is_removed TINYINT(1) DEFAULT 0,
ADD COLUMN temp_filename VARCHAR(255) NULL;

-- Add index for better performance
CREATE INDEX idx_product_images_removed ON product_images(product_id, is_removed);
