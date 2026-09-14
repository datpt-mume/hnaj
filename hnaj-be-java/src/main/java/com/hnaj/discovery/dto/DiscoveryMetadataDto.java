package com.hnaj.discovery.dto;

import java.util.List;

public record DiscoveryMetadataDto(
        List<CategoryDto> categories,
        List<DistrictDto> districts,
        List<TagDto> tags
) {}
