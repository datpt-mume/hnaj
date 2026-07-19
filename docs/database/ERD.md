# Database ERD (Target)

Đây là thiết kế mục tiêu. Laravel migrations là nguồn thực thi khi schema được triển khai; các bảng dưới đây chưa đồng nghĩa với việc đã tồn tại trong scaffold.

```mermaid
erDiagram
    USERS ||--o{ USER_ROLES : has
    ROLES ||--o{ USER_ROLES : grants
    USERS }o--o{ PLACES : manages
    CATEGORIES ||--o{ PLACES : classifies
    ADMINISTRATIVE_AREAS ||--o{ PLACES : district_or_ward
    ADMINISTRATIVE_AREAS ||--o{ ADMINISTRATIVE_AREAS : contains
    PLACES }o--o{ TAGS : classified_by
    PLACES ||--o{ PLACE_EXTERNAL_IDS : identifies
    PLACES ||--o{ MEDIA : contains
    PLACES ||--o{ OPENING_HOURS : opens
    USERS ||--o{ FAVORITES : creates
    PLACES ||--o{ FAVORITES : receives
    USERS ||--o{ RECOMMENDATION_HISTORY : receives
    PLACES ||--o{ RECOMMENDATION_HISTORY : appears_in
    USERS ||--o{ REVIEWS : writes
    PLACES ||--o{ REVIEWS : receives
    REVIEWS ||--o{ MEDIA : attaches
    REVIEWS ||--o{ REVIEW_REPORTS : reported
    USERS ||--o{ REVIEW_REPORTS : submits
    REVIEWS ||--o| OWNER_RESPONSES : receives
    USERS ||--o{ OWNER_RESPONSES : writes
    TAGS ||--o{ REVIEW_TAGS : labels
    REVIEWS ||--o{ REVIEW_TAGS : has
    USERS ||--o{ ANALYTICS_EVENTS : generates

    USERS { string id PK string email string status }
    ROLES { string id PK string name UK }
    USER_ROLES { string user_id FK string role_id FK }
    PLACES { string id PK string slug UK string status decimal latitude decimal longitude }
    ADMINISTRATIVE_AREAS { string id PK string name string type string parent_id FK string city boolean is_active }
    CATEGORIES { string id PK string slug UK string name boolean is_active }
    TAGS { string id PK string slug UK string status string group }
    PLACE_EXTERNAL_IDS { string id PK string place_id FK string provider string external_id UK }
    MEDIA { string id PK string owner_type string owner_id string path }
    OPENING_HOURS { string id PK string place_id FK int weekday time opens_at time closes_at }
    FAVORITES { string user_id FK string place_id FK }
    RECOMMENDATION_HISTORY { string id PK string user_id nullable string anonymous_id nullable string place_id FK timestamp recommended_at }
    REVIEWS { string id PK string user_id FK string place_id FK tinyint rating string status }
    REVIEW_REPORTS { string id PK string review_id FK string user_id FK string status }
    OWNER_RESPONSES { string id PK string review_id FK string user_id FK text body }
    REVIEW_TAGS { string review_id FK string tag_id FK }
    ANALYTICS_EVENTS { string id PK string user_id nullable string anonymous_id nullable string event_name json properties }
```

Implementation note: taxonomy is now normalized through `categories`, `tags` and `place_tag`. `places.category_id` stores exactly one primary category. External source identifiers are stored separately so Google identifiers can be deduplicated without coupling the place model to one provider.

## Rules

- Public identifiers use ULID.
- Every place represents one branch/location in the MVP.
- Money is stored as integer VND; no floating-point money fields.
- Coordinates use WGS 84 decimal latitude/longitude.
- Published and non-deleted places are eligible for public recommendation.
- Recommendation history is retained for 30 days; raw analytics for 12 months.
- Relationship tables require unique constraints appropriate to their business rule, such as one favorite per user/place and one review per user/place.
- Proposed names above must be reconciled with actual migrations before implementation.
