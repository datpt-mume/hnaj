package com.hnaj.auth.config;

import org.springframework.boot.context.properties.EnableConfigurationProperties;
import org.springframework.context.annotation.Configuration;

@Configuration
@EnableConfigurationProperties(HnajProperties.class)
public class HnajAuthConfiguration {
}