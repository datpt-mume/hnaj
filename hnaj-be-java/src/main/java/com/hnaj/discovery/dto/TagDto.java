package com.hnaj.discovery.dto;

import com.hnaj.discovery.entity.Tag;

public record TagDto(Long id, String name, String slug) {

    public static TagDto from(Tag tag) {
        return new TagDto(tag.getId(), tag.getName(), tag.getSlug());
    }
}
