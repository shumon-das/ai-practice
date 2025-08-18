import * as dotenv from 'dotenv';
dotenv.config();
import fetch from "node-fetch";

async function askHuggingFace(question) {
  const response = await fetch(
    "https://api-inference.huggingface.co/models/mistralai/mistral-7b-instruct-v0.3",
    {
      method: "POST",
      headers: {
        Authorization: `Bearer ${process.env.HF_TOKEN}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        inputs: `You are a helpful assistant.\nUser: ${question}\nAssistant:`,
        parameters: {
          max_new_tokens: 200,
          temperature: 0.7,
        },
      }),
    }
  );

  if (!response.ok) {
    console.error("Error:", response.status, await response.text());
    return;
  }

  const data = await response.json();
  console.log(data[0]?.generated_text || data);
}

askHuggingFace("Write a short Bengali poem about friendship.");
