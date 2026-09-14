package com.hnaj.discovery.repository;

import com.hnaj.discovery.entity.District;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.List;

public interface DistrictRepository extends JpaRepository<District, Long> {

    @Query("SELECT d FROM District d WHERE d.status = 'active' ORDER BY d.name")
    List<District> findActiveDistricts();
}
