package com.hnaj.discovery.service;

import com.hnaj.discovery.dto.CategoryDto;
import com.hnaj.discovery.dto.DiscoveryMetadataDto;
import com.hnaj.discovery.dto.DistrictDto;
import com.hnaj.discovery.dto.TagDto;
import com.hnaj.discovery.repository.CategoryRepository;
import com.hnaj.discovery.repository.DistrictRepository;
import com.hnaj.discovery.repository.TagRepository;
import org.springframework.stereotype.Service;

import java.util.List;

/**
 * Fetches active taxonomy metadata for the discovery filter UI.
 * Equivalent to Laravel GetDiscoveryMetadata action + DiscoveryMetadataRepository.
 * Query returns only non-deleted, active items sorted by name.
 */
@Service
public class DiscoveryMetadataService {

    private final CategoryRepository categoryRepository;
    private final DistrictRepository districtRepository;
    private final TagRepository tagRepository;

    public DiscoveryMetadataService(
            CategoryRepository categoryRepository,
            DistrictRepository districtRepository,
            TagRepository tagRepository) {
        this.categoryRepository = categoryRepository;
        this.districtRepository = districtRepository;
        this.tagRepository = tagRepository;
    }

    public DiscoveryMetadataDto get() {
        List<CategoryDto> categories = categoryRepository.findActiveCategories()
                .stream().map(CategoryDto::from).toList();
        List<DistrictDto> districts = districtRepository.findActiveDistricts()
                .stream().map(DistrictDto::from).toList();
        List<TagDto> tags = tagRepository.findActiveTags()
                .stream().map(TagDto::from).toList();
        return new DiscoveryMetadataDto(categories, districts, tags);
    }
}
