# php

This project is a PHP-based application that matches a candidate's resume (in JSON format) against a list of job descriptions (also in JSON format). It uses the OpenAI GPT-4 API to compute a relevance score based on experience, skills, projects, and qualifications.

✨ Features
    *  Upload a resume (JSON format) via a simple HTML form
    *  Preprocess and filter job descriptions using hard rules:
       ✅ Degree match
       ✅ Minimum experience
       ✅ Required certifications
    *  Score remaining JDs using GPT-4 with weighted logic:
       Experience (40%)
       Skills (35%)
       Projects (23%)
       Certifications + Education (2%)
