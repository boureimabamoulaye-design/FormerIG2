package com.qcm.model;

public class Question {
    private String enonce;
    private String[] choix;
    private int bonneReponse;

    public Question(String enonce, String[] choix, int bonneReponse) {
        this.enonce = enonce;
        this.choix = choix;
        this.bonneReponse = bonneReponse;
    }

    public String getEnonce() {
        return enonce;
    }

    public String[] getChoix() {
        return choix;
    }

    public int getBonneReponse() {
        return bonneReponse;
    }
}
