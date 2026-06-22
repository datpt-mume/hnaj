-- HNaj Database Migration 001: Initial Schema
-- Requires: PostgreSQL 14+ with PostGIS 3+ extension

-- Enable PostGIS extension
CREATE EXTENSION IF NOT EXISTS postgis;

-- ============================================================
-- Tags table: Danh mục ngữ cảnh
-- ============================================================
CREATE TABLE IF NOT EXISTS tags (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    emoji       VARCHAR(10),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Places table: Thông tin địa điểm
-- ============================================================
CREATE TABLE IF NOT EXISTS places (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name         VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL UNIQUE,
    cover_image  TEXT,
    address      TEXT,
    location     GEOGRAPHY(Point, 4326) NOT NULL,
    price_min    INTEGER NOT NULL DEFAULT 0,
    price_max    INTEGER NOT NULL DEFAULT 0,
    rating       DECIMAL(2,1) DEFAULT 0.0 CHECK (rating >= 0 AND rating <= 5),
    is_active    BOOLEAN NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Spatial index for fast radius queries
CREATE INDEX IF NOT EXISTS idx_places_location
    ON places USING GIST (location);

-- Index for price filtering
CREATE INDEX IF NOT EXISTS idx_places_price_min
    ON places (price_min);

-- Index for active places
CREATE INDEX IF NOT EXISTS idx_places_active
    ON places (is_active) WHERE is_active = TRUE;

-- ============================================================
-- Place_Tag table: Many-to-Many relationship
-- ============================================================
CREATE TABLE IF NOT EXISTS place_tag (
    place_id    UUID NOT NULL REFERENCES places(id) ON DELETE CASCADE,
    tag_id      UUID NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (place_id, tag_id)
);

CREATE INDEX IF NOT EXISTS idx_place_tag_place_id ON place_tag (place_id);
CREATE INDEX IF NOT EXISTS idx_place_tag_tag_id ON place_tag (tag_id);

-- ============================================================
-- Seed data: Tags mặc định
-- ============================================================
INSERT INTO tags (name, slug, emoji) VALUES
    ('Hẹn hò',       'hen-ho',        '💑'),
    ('Giá sinh viên', 'gia-sinh-vien', '🎓'),
    ('Riêng tư',     'rieng-tu',       '🤫'),
    ('Trong nhà',    'trong-nha',      '🏠'),
    ('Ngoài trời',   'ngoai-troi',     '🌳'),
    ('Thể thao',     'the-thao',       '⚽'),
    ('Vận động',     'van-dong',       '🏃'),
    ('Thư giãn',     'thu-gian',       '🧘'),
    ('Sang trọng',   'sang-trong',     '✨'),
    ('Thú cưng',     'thu-cung',       '🐶'),
    ('Gia đình',     'gia-dinh',       '👨‍👩‍👧‍👦'),
    ('Làm việc',     'lam-viec',       '💻')
ON CONFLICT (slug) DO NOTHING;

-- ============================================================
-- Trigger: Auto-update updated_at
-- ============================================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_tags_updated_at
    BEFORE UPDATE ON tags
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_places_updated_at
    BEFORE UPDATE ON places
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
