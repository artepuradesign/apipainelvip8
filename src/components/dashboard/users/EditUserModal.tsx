
import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, Calendar, Clock } from "lucide-react";
import { getFullApiUrl } from '@/utils/apiHelper';
import type { User } from "@/types/user";
import { format, differenceInDays, parseISO } from "date-fns";
import { ptBR } from "date-fns/locale";

interface Plan {
  id: number;
  name: string;
  slug: string;
  price: number;
  priceFormatted: string;
}

interface EditUserModalProps {
  user: User | null;
  isOpen: boolean;
  onClose: () => void;
  onSave: () => void;
  onUserChange: (user: User) => void;
}

const EditUserModal = ({ user, isOpen, onClose, onSave, onUserChange }: EditUserModalProps) => {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loadingPlans, setLoadingPlans] = useState(false);

  useEffect(() => {
    if (isOpen) {
      fetchPlans();
    }
  }, [isOpen]);

  const fetchPlans = async () => {
    setLoadingPlans(true);
    try {
      const response = await fetch(getFullApiUrl('/plans/active'));
      const result = await response.json();
      if (result.success && result.data) {
        setPlans(result.data);
      }
    } catch (error) {
      console.error('Erro ao carregar planos:', error);
    } finally {
      setLoadingPlans(false);
    }
  };

  if (!user) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Editar Usuário</DialogTitle>
        </DialogHeader>
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label htmlFor="edit-name">Nome Completo</Label>
              <Input
                id="edit-name"
                value={user.name}
                onChange={(e) => onUserChange({ ...user, name: e.target.value })}
              />
            </div>
            <div>
              <Label htmlFor="edit-email">E-mail</Label>
              <Input
                id="edit-email"
                value={user.email}
                onChange={(e) => onUserChange({ ...user, email: e.target.value })}
              />
            </div>
            <div>
              <Label htmlFor="edit-balance">Saldo da Carteira</Label>
              <Input
                id="edit-balance"
                type="number"
                step="0.01"
                value={user.balance}
                onChange={(e) => onUserChange({ ...user, balance: parseFloat(e.target.value) || 0 })}
              />
            </div>
            <div>
              <Label htmlFor="edit-plan-balance">Saldo do Plano</Label>
              <Input
                id="edit-plan-balance"
                type="number"
                step="0.01"
                value={user.planBalance || 0}
                onChange={(e) => onUserChange({ ...user, planBalance: parseFloat(e.target.value) || 0 })}
              />
            </div>
            <div>
              <Label htmlFor="edit-plan-discount" className="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                Desconto do Plano (%)
              </Label>
              <Input
                id="edit-plan-discount"
                type="number"
                min="0"
                max="100"
                value={user.planDiscount || 0}
                onChange={(e) => onUserChange({ ...user, planDiscount: parseInt(e.target.value) || 0 })}
              />
            </div>
            <div>
              <Label htmlFor="edit-plan">Plano</Label>
              {loadingPlans ? (
                <div className="flex items-center gap-2 h-10 px-3 border rounded-md">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  <span className="text-sm text-muted-foreground">Carregando planos...</span>
                </div>
              ) : (
                <Select
                  value={user.plan}
                  onValueChange={(value) => onUserChange({ ...user, plan: value })}
                >
                  <SelectTrigger id="edit-plan">
                    <SelectValue placeholder="Selecione um plano" />
                  </SelectTrigger>
                  <SelectContent>
                    {plans.map((plan) => (
                      <SelectItem key={plan.id} value={plan.name}>
                        {plan.name} - {plan.priceFormatted}
                      </SelectItem>
                    ))}
                    {plans.length === 0 && (
                      <SelectItem value={user.plan} disabled={false}>
                        {user.plan}
                      </SelectItem>
                    )}
                  </SelectContent>
                </Select>
              )}
            </div>
            <div>
              <Label htmlFor="edit-plan-start" className="flex items-center gap-1">
                <Calendar className="h-3.5 w-3.5" /> Início do Plano
              </Label>
              <Input
                id="edit-plan-start"
                value={user.planStartDate ? format(parseISO(user.planStartDate), 'dd/MM/yyyy', { locale: ptBR }) : 'Não definido'}
                readOnly
                className="bg-muted cursor-default"
              />
            </div>
            <div>
              <Label htmlFor="edit-plan-end" className="flex items-center gap-1">
                <Calendar className="h-3.5 w-3.5" /> Término do Plano
              </Label>
              <Input
                id="edit-plan-end"
                value={user.planEndDate ? format(parseISO(user.planEndDate), 'dd/MM/yyyy', { locale: ptBR }) : 'Não definido'}
                readOnly
                className="bg-muted cursor-default"
              />
            </div>
            <div>
              <Label htmlFor="edit-days-remaining" className="flex items-center gap-1">
                <Clock className="h-3.5 w-3.5" /> Dias Restantes
              </Label>
              <Input
                id="edit-days-remaining"
                value={user.planEndDate ? Math.max(0, differenceInDays(parseISO(user.planEndDate), new Date())) + ' dias' : 'N/A'}
                readOnly
                className="bg-muted cursor-default"
              />
            </div>
          </div>
          <div>
            <Label htmlFor="edit-notes">Observações</Label>
            <Textarea
              id="edit-notes"
              value={user.notes || ''}
              onChange={(e) => onUserChange({ ...user, notes: e.target.value })}
              placeholder="Escreva uma observação que será enviada como notificação ao usuário..."
            />
          </div>
          <div className="flex gap-2">
            <Button onClick={onSave}>Salvar Alterações</Button>
            <Button variant="outline" onClick={onClose}>Cancelar</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default EditUserModal;
