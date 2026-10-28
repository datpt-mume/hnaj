import { spawnSync } from "node:child_process";
import path from "node:path";

const rootDir = path.resolve(import.meta.dirname, "..");
const frontendDir = path.join(rootDir, "frontend");
const backendDir = path.join(rootDir, "backend");
const isWindows = process.platform === "win32";

function runStep(label, command, args, cwd) {
  console.log(`\n=== ${label} ===`);
  const result = spawnSync(command, args, {
    cwd,
    stdio: "inherit",
    shell: isWindows,
  });

  if (result.error) {
    console.error(`Failed to start ${command}:`, result.error.message);
    process.exit(1);
  }

  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

const mode = process.argv[2];

if (mode === "typecheck") {
  runStep("Frontend TypeScript Check", "npm", ["run", "typecheck"], frontendDir);
  runStep(
    "Backend Java Test-Compile Check",
    isWindows ? ".\\mvnw.cmd" : "./mvnw",
    ["-B", "test-compile"],
    backendDir
  );
} else if (mode === "test") {
  runStep("Frontend Unit & Component Tests", "npm", ["test"], frontendDir);
  runStep(
    "Backend Spring Boot Tests",
    isWindows ? ".\\mvnw.cmd" : "./mvnw",
    ["-B", "test"],
    backendDir
  );
} else {
  console.error(`Unknown mode: ${mode ?? "(none)"}. Expected "typecheck" or "test".`);
  process.exit(1);
}
