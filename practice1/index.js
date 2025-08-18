import * as dotenv from 'dotenv';
dotenv.config();

import { PDFLoader } from '@langchain/community/document_loaders/fs/pdf';
import { RecursiveCharacterTextSplitter } from '@langchain/textsplitters';
import { GoogleGenerativeAIEmbeddings } from '@langchain/google-genai';
import { Pinecone } from '@pinecone-database/pinecone';
import { PineconeStore } from '@langchain/pinecone';



const PDF_PATH = './dsa.pdf';
const pdfLoader = new PDFLoader(PDF_PATH);
const rawDocs = await pdfLoader.load();

const textSplitter = new RecursiveCharacterTextSplitter({
    chunkSize: 1000,
    chunkOverlap: 200,
  });
const chunkedDocs = await textSplitter.splitDocuments(rawDocs);


const embeddings = new GoogleGenerativeAIEmbeddings({
    apiKey: process.env.GEMINI_API_KEY,
    model: 'text-embedding-004',
  });


const pinecone = new Pinecone();
const pineconeIndex = pinecone.Index(process.env.PINECONE_INDEX_NAME);


await PineconeStore.fromDocuments(chunkedDocs, embeddings, {
    pineconeIndex,
    maxConcurrency: 5,
  });
