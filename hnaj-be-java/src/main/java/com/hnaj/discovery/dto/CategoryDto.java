package com.hnaj.discovery.dto;

import com.hnaj.discovery.entity.Category;

public record CategoryDto(Long id, String name, String slug) {

    public static CategoryDto from(Category category) {
        return new CategoryDto(category.getId(), category.getName(), category.getSlug());
    }
}
