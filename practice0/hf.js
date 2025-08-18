import { InferenceClient } from "@huggingface/inference";
import { createRepo, commit, deleteRepo, listFiles } from "@huggingface/hub";
import { McpClient } from "@huggingface/mcp-client";
// import type { RepoId } from "@huggingface/hub";

import { Agent } from '@huggingface/mcp-client';

const agent = new Agent({
  provider: "auto",
  model: "Qwen/Qwen2.5-72B-Instruct",
  apiKey: process.env.HF_TOKEN,
  servers: [
    {
      // Playwright MCP
      command: "npx",
      args: ["@playwright/mcp@latest"],
    },
  ],
});

await agent.loadTools();

for await (const chunk of agent.run("generate a flower image with white background and red petals")) {
    if ("choices" in chunk) {
        const delta = chunk.choices[0]?.delta;
        if (delta.content) {
            console.log(delta.content);
        }
    }
}