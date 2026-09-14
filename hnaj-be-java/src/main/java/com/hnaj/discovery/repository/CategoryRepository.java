package com.hnaj.discovery.repository;

import com.hnaj.discovery.entity.Category;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.List;

public interface CategoryRepository extends JpaRepository<Category, Long> {

    @Query("SELECT c FROM Category c WHERE c.status = 'active' AND c.deletedAt IS NULL ORDER BY c.name")
    List<Category> findActiveCategories();
}
