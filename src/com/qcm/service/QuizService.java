package com.qcm.service;

import com.qcm.model.Question;
import java.util.*;

public class QuizService {

    private List<Question> questions;
    private int index;
    private double score;

    public QuizService() {
        questions = new ArrayList<>();
        chargerQuestions();
        melangerQuestions();
        index = 0;
        score = 0;
    }

    private void chargerQuestions() {
        questions.add(new Question("Capitale du Mali ?",
                new String[]{"Kayes", "Bamako", "Sikasso", "Mopti"}, 1));

        questions.add(new Question("2 + 2 = ?",
                new String[]{"3", "4", "5", "6"}, 1));

        questions.add(new Question("Java est ?",
                new String[]{"Langage", "Base de données", "OS", "Navigateur"}, 0));

        questions.add(new Question("HTML signifie ?",
                new String[]{"HyperText Markup Language", "HighText Machine Language", "HyperLoop", "Aucun"}, 0));
    }

    private void melangerQuestions() {
        Collections.shuffle(questions);
    }

    public Question getQuestionCourante() {
        return questions.get(index);
    }

    public void verifierReponse(int reponseUtilisateur) {
        if (reponseUtilisateur == questions.get(index).getBonneReponse()) {
            score += 1;
        } else {
            score -= 0.25; // pénalité
        }
    }

    public boolean questionSuivante() {
        index++;
        return index < questions.size();
    }

    public double getScore() {
        return score;
    }

    public int getTotalQuestions() {
        return questions.size();
    }

    public int getIndex() {
        return index + 1;
    }
}
