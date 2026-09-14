# Shared Maven source stage; tests run only when the test service is invoked.
FROM maven:3.9.9-eclipse-temurin-21 AS source
WORKDIR /workspace

COPY pom.xml ./
RUN mvn -B -q -DskipTests dependency:go-offline
COPY src ./src

FROM source AS backend-java-test
CMD ["mvn", "-B", "test", "-Dspring.profiles.active=test"]

FROM source AS build
RUN mvn -B -q -DskipTests package

FROM eclipse-temurin:21-jre-alpine AS runtime
WORKDIR /app

# Non-root user (AGENTS.md §11.1)
RUN addgroup -S hnaj && adduser -S hnaj -G hnaj

# Chỉ copy artifact cần thiết
COPY --from=build /workspace/target/hnaj-be-java-*.jar /app/app.jar

# Cấp quyền và chuyển sang non-root
RUN chown -R hnaj:hnaj /app
USER hnaj

# Health check gọi endpoint thật
HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=5 \
  CMD wget -qO- http://127.0.0.1:8083/actuator/health || \
      wget -qO- http://127.0.0.1:8083/api/test || exit 1

EXPOSE 8083

# JVM flags phù hợp container
ENV JAVA_OPTS="-XX:+UseG1GC -XX:MaxRAMPercentage=75.0 -Dfile.encoding=UTF-8"

ENTRYPOINT ["sh", "-c", "exec java $JAVA_OPTS -jar /app/app.jar"]
