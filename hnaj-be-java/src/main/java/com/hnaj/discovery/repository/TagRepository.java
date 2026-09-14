package com.hnaj.discovery.repository;

import com.hnaj.discovery.entity.Tag;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.List;

public interface TagRepository extends JpaRepository<Tag, Long> {

    @Query("SELECT t FROM Tag t WHERE t.status = 'active' AND t.deletedAt IS NULL ORDER BY t.name")
    List<Tag> findActiveTags();
}
