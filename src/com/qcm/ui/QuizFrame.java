package com.qcm.ui;

import com.qcm.model.Question;
import com.qcm.service.QuizService;

import javax.swing.*;
import java.awt.*;

public class QuizFrame extends JFrame {

    private QuizService service;

    private JLabel lblQuestion, lblScore, lblProgression;
    private JRadioButton[] choix;
    private ButtonGroup groupe;
    private JButton btnSuivant;

    public QuizFrame() {
        service = new QuizService();

        setTitle("QCM - Application");
        setSize(600, 400);
        setLocationRelativeTo(null);
        setDefaultCloseOperation(EXIT_ON_CLOSE);

        initUI();
        afficherQuestion();
    }

    private void initUI() {
        lblQuestion = new JLabel();
        lblQuestion.setFont(new Font("Arial", Font.BOLD, 18));

        lblScore = new JLabel("Score : 0");
        lblProgression = new JLabel("Question 1");

        JPanel topPanel = new JPanel(new GridLayout(2,1));
        topPanel.add(lblProgression);
        topPanel.add(lblScore);

        choix = new JRadioButton[4];
        groupe = new ButtonGroup();
        JPanel panelChoix = new JPanel(new GridLayout(4,1));

        for (int i = 0; i < 4; i++) {
            choix[i] = new JRadioButton();
            groupe.add(choix[i]);
            panelChoix.add(choix[i]);
        }

        btnSuivant = new JButton("Suivant");
        btnSuivant.addActionListener(e -> traiterReponse());

        setLayout(new BorderLayout());
        add(topPanel, BorderLayout.NORTH);
        add(lblQuestion, BorderLayout.CENTER);
        add(panelChoix, BorderLayout.WEST);
        add(btnSuivant, BorderLayout.SOUTH);
    }

    private void afficherQuestion() {
        Question q = service.getQuestionCourante();

        lblQuestion.setText(q.getEnonce());
        lblScore.setText("Score : " + service.getScore());
        lblProgression.setText("Question " + service.getIndex());

        for (int i = 0; i < 4; i++) {
            choix[i].setText(q.getChoix()[i]);
        }

        groupe.clearSelection();
    }

    private void traiterReponse() {
        int reponse = -1;

        for (int i = 0; i < 4; i++) {
            if (choix[i].isSelected()) {
                reponse = i;
            }
        }

        if (reponse == -1) {
            JOptionPane.showMessageDialog(this, "Veuillez choisir une réponse !");
            return;
        }

        service.verifierReponse(reponse);

        if (service.questionSuivante()) {
            afficherQuestion();
        } else {
            afficherResultat();
        }
    }

    private void afficherResultat() {
        JOptionPane.showMessageDialog(this,
                "Score final : " + service.getScore() + "/" + service.getTotalQuestions());

        dispose();
    }
}
