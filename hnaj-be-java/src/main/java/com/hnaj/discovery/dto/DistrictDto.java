package com.hnaj.discovery.dto;

import com.hnaj.discovery.entity.District;

public record DistrictDto(Long id, String name, String code) {

    public static DistrictDto from(District district) {
        return new DistrictDto(district.getId(), district.getName(), district.getCode());
    }
}
